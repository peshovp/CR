#!/usr/bin/env python
# -*- coding: utf-8 -*-
import http.server as HTTPServer
import socketserver as SocketServer

'''
    GPL-3.0-only or GPL-3.0-or-later

    «Copyright 2020 Juan Morillo, Javier Guerrero, Ruben Molina, Domingo Solomando»

    This file is part of GeoMaxima NTRIP Caster.

    GeoMaxima NTRIP Caster is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    GeoMaxima NTRIP Caster is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Foobar.  If not, see <http://www.gnu.org/licenses/>.
	
	Based on CasterREP (GPL-3.0) by Juan Morillo, Javier Guerrero, Ruben Molina, Domingo Solomando

    ________________________________________________________________________________

      *          .    +    ██████╗       ███████╗      ██████╗         .     *
           .               ██╔══██╗      ██╔════╝      ██╔══██╗                  .
                 *         ██████╔╝      █████╗        ██████╔╝      +
         +             .   ██╔══██╗      ██╔══╝        ██╔═══╝             +       .
                           ██║   ██║     ███████╗      ██║          *
           .       *       ╚═╝   ╚═╝     ╚══════╝      ╚═╝                      *
    ____________________     ~ ~    C A S T E R   3.0  ~ ~       ____________________

@File: geomaxima_caster_server.py
@Author: GeoMaxima (Based on CasterREP by Juan Morillo, Javier Guerrero, Ruben Molina, Domingo Solomando)
@Version: 3.0.20201010
@Info: This script manages all clients requests to the caster
'''
__author___ = "Juan Morillo, Javier Guerrero, Ruben Molina, Domingo Solomando"
__version__ = "v3.0.20201010"
__license__ = "GNU General Public License (GPL-3.0-only or GPL-3.0-or-later)"
__description__ = "Caster Server: This script manage all clients requests to the caster"


from geomaxima_header_printer import printCasterHeader
from geomaxima_RoverUserClass import RoverUser
from geomaxima_GPGGADecoder import *

import platform
import codecs
import time
import base64
import bcrypt
import datetime
import os
import sys

import logging
import logging.config
from logging.handlers import RotatingFileHandler
import socket
import select
import errno
import json
import pymongo
from bson.binary import Binary

from config_load import Load_config
from general_defs import getDatabase

conf=Load_config()

logger = logging.getLogger(__name__)

def checkMountpointInDatabase(mountp, logger):
    valid = False
    logger.debug("Checking mountpoint: %s", mountp)
    try:
        logger.info("Checking for mountpoint request "+mountp+"...")
        db = getDatabase()
        db_rtcm_raw = db[conf['PROFILE']['DATABASE']['str_db_RTCMTable']]
        db_streams = db[conf['PROFILE']['DATABASE']['str_db_StreamsTable']]
        stream = db_streams.find_one({'mountpoint': mountp})
        logger.debug("Stream data: %s", stream)
        if stream == None:
            logger.info('ERROR - Mount Point Invalid - It does not exist on database')
            valid = False
        elif not stream['active']:
            logger.info('ERROR - Mount Point Invalid - It is not active on database')
            valid = False
        elif not stream['solution']:
            rtcm_raw_data = db_rtcm_raw.find_one({'mountpoint': mountp})
            if ((time.time() - rtcm_raw_data['timestamp']) > conf['SETTINGS']['PREFERENCES']['TIME_OUT_RAWDATA']):
                logger.warning("Invalid data received")
                valid = False
            else:
                logger.debug("Valid data received")
                valid = True
        else:
            valid = True
    except Exception as err:
        logger.info("EXCEPTION searching for mountpoint in MongoDB:" + str(err))
        valid = False
    finally:
        return valid

def checkAuth(input_token, logger):
    """Verify NTRIP client credentials from Base64 Authorization header.

    Decodes the Base64 token to extract username:password, then verifies
    against bcrypt password_hash in MongoDB. Supports backward-compatible
    migration from legacy base64 token_auth field.

    Returns (True, username) on success, (False, None) on failure.
    """
    valid_auth = False
    username = None
    try:
        # Decode Base64 Authorization header to get username:password
        token = base64.b64decode(input_token)
        value = token.decode('utf-8')
        parts = value.split(':', 1)
        if len(parts) != 2:
            return False, None
        username = parts[0]
        password = parts[1].strip()

        db = getDatabase()
        db_users = db[conf['PROFILE']['DATABASE']['str_db_UsersTable']]

        user = db_users.find_one({'username': username, 'active': True})
        if user is None:
            return False, None

        # Primary: bcrypt verification
        if 'password_hash' in user and user['password_hash']:
            if bcrypt.checkpw(password.encode('utf-8'), user['password_hash'].encode('utf-8')):
                valid_auth = True
        # Backward compatibility: legacy base64 token_auth
        elif 'token_auth' in user and user['token_auth']:
            if user['token_auth'] == input_token:
                valid_auth = True
                # Migrate to bcrypt
                new_hash = bcrypt.hashpw(password.encode('utf-8'), bcrypt.gensalt()).decode('utf-8')
                db_users.update_one(
                    {'_id': user['_id']},
                    {'$set': {'password_hash': new_hash}, '$unset': {'token_auth': ''}}
                )
                logger.info(f"Migrated user '{username}' from token_auth to bcrypt")

    except (ValueError, UnicodeDecodeError, KeyError) as e:
        logger.error(f"Error decoding credentials: {e}", exc_info=True)
    except Exception as e:
        logger.error(f"Error during authentication: {e}", exc_info=True)
    finally:
        if valid_auth:
            return True, username
        else:
            return False, None

class CasterRequestHandler(HTTPServer.BaseHTTPRequestHandler):
    server_version = "NTRIP_GeoMaxima_V5.0.0/1.0"
    sys_version = ""
    logger_rh = logging.getLogger("CasterReqHandler")
    logger_rh.setLevel(logging.INFO)
    handler = RotatingFileHandler('./caster_request_handler.log', maxBytes=1000000, backupCount=5)
    formatter = logging.Formatter('%(asctime)s - %(message)s')
    handler.setFormatter(formatter)
    logger_rh.addHandler(handler)
    
    def __init__(self, request, client_address, server):
        self.logger_rh.debug("Initializing request handler")
        HTTPServer.BaseHTTPRequestHandler.__init__(self, request, client_address, server)
        self.logger_rh.debug("HTTP server handler initialized")
        self.protocol_version = 'HTTP/1.0'        

    def do_GET(self):
        self.logger_rh.debug("Processing GET request")
        self.handle_data()

    def do_UNAUTHORIZED(self):
        self.send_response(401)
        self.send_header('WWW-Authenticate', 'Basic realm=\"None\"')
        self.send_header('Content-type', 'text/html')
        self.end_headers()
        self.wfile.write(bytes('No auth header received or not authenticated\r\n',"utf-8"))

    def do_SOURCETABLE(self):
        mountpoints = ''
        try:
            db = getDatabase()
            db_rtcm_raw = db[conf['PROFILE']['DATABASE']['str_db_RTCMTable']]
            db_streams = db[conf['PROFILE']['DATABASE']['str_db_StreamsTable']]
            streams = db_streams.find()

            if streams == None:
                self.logger_rh.info(str(self.client_address)+' - Ops!! It seems "streams" collection is empty!!')
            else:
                for st in streams:
                    if st['solution'] == 0:
                        stream_data = db_rtcm_raw.find_one({'mountpoint': st['mountpoint']})
                        if stream_data == None:
                            continue
                        elif ((time.time() - stream_data['timestamp']) > conf['SETTINGS']['PREFERENCES']['TIME_OUT_RAWDATA']):
                            continue
                    mp = list()
                    mp.append(st['mountpoint'])
                    mp.append(st['identifier'])
                    mp.append(st['data_format'])
                    mp.append(st['format_detail'])
                    mp.append(str(st['carrier']))
                    mp.append(st['nav_system'])
                    mp.append(st['network'])
                    mp.append(st['country'])
                    mp.append(str(st['latitude']))
                    mp.append(str(st['longitude']))
                    mp.append(str(st['nmea']))
                    if not st['solution']:
                        mp.append('0')
                    else:
                        mp.append('1')
                    mp.append(st['generator'])
                    mp.append(st['compr_encryp'])
                    mp.append(st['authentication'])
                    mp.append(str(st['fee']))
                    mp.append(str(st['bitrate']))
                    mp.append(st['misc'])
                    mp_string = ';'.join(mp)
                    mountpoints += "STR;"+mp_string+"\r\n"
        except pymongo.errors.ServerSelectionTimeoutError as err:
            self.logger_rh.info(str(self.client_address)+" - EXCEPTION searching for streams in MongoDB database:" + str(err))
        
        finally:
            status = "SOURCETABLE 200 OK\r\n"
            server = "Server: "+self.server_version+"\r\n"
            date = "Date: "+time.strftime("%a, %d %b %Y %H:%M:%S GMT Standar Time", time.gmtime())+"\r\n"
            content_type = "Content-Type: text/plain; charset=ISO-8859-1\r\n"
            connection = "Connection: Close\r\n"
            content_lenght = "Content-Length: "+str(len(mountpoints))+"\r\n\r\n"
            end = "ENDSOURCETABLE\r\n"
            self.wfile.write(bytes(status+server+date+content_type+connection+content_lenght+mountpoints+end,"utf-8"))

    def handle_data(self):
        self.logger_rh.info(str(self.client_address)+" - New client. Request "+str(self.path) + ", -Version %r,  - Header %r" % (self.request_version, self.headers.items()))
        self.data = ""
        self.timestamp = 0.0
        self.stream_data = None
        self.last_write_time = 0.0

        if self.path == "/":
            self.logger_rh.info(str(self.client_address)+" - Send sourcetable to client")
            self.do_SOURCETABLE()
            return

        self.db = getDatabase()
        self.db_rtcm_raw = self.db[conf['PROFILE']['DATABASE']['str_db_RTCMTable']]
        self.db_rover_conn = self.db[conf['PROFILE']['DATABASE']['str_db_RoverConnections']]
        

        self.path = str(self.path.replace("/","").strip())
        valid_mountp = checkMountpointInDatabase(self.path, self.logger_rh)
        
        if not valid_mountp:
            self.logger_rh.info(str(self.client_address)+" - Send sourcetable to client")
            self.do_SOURCETABLE()
        else:
            self.logger_rh.info(str(self.client_address)+" - Mountpoint is valid in our database")
            valid_auth = False  # Initially false
            try:
                auth = self.headers.get('Authorization')
                   
                if auth == None :
                    valid_auth = False
                else:
                    if 'Basic' in auth:
                        token_auth = auth.split(" ")[1]
                        valid_auth, username = checkAuth(token_auth, self.logger_rh)
            except Exception as e:
                self.logger_rh.info(str(self.client_address)+" - EXCEPTION: We got an issue with the authentication: "+str(e))
            
            if valid_auth:
                self.wfile.write(bytes("ICY 200 OK\r\n\r\n","utf-8"))
                self.logger_rh.info(str(self.client_address)+" - User authentication was valid: "+username)
                
                ts_init = time.time()
                self.last_write_time = ts_init
                
                self.rover = RoverUser(self.client_address, self.headers.get('User-Agent'), self.path, username, ts_init)
                self.need_GGA = False
                self.got_GGA = False
                
                if self.path == conf['SETTINGS']['PREFERENCES']['STR_NEAREST_MOUNPOINT']:
                    self.need_GGA = True
                    self.logger_rh.info(str(self.client_address)+" - Waiting for user's NMEA GGA message.")
                    
                while 1:
                    ts_current = time.time()
                    elapsed = ts_current - ts_init
                    
                    if elapsed > 5.0 and self.need_GGA and not self.got_GGA:
                        self.logger_rh.info(str(self.client_address)+" - Rover user "+str(username)+" rejected. No GPGGA message received. Access denied.")
                        self.wfile.write(bytes('No GPGGA message received. Access denied.',"utf-8"))
                        break
                    try:
                        readable, writable, exceptional = select.select(
                            [self.rfile], [self.wfile], [self.rfile,self.wfile]) 

                        for s in readable:  
                            self.data = None
                            self.logger_rh.debug("Readable socket: %s", s)
                            self.data = s.readline()
                            self.logger_rh.debug("Data from user: %s", self.data)
                            if self.data:
                                self.logger_rh.info(str(self.client_address)+" - Message got from user: "+str(self.data.strip()))
                                if b'GGA' in self.data:
                                    self.got_GGA = True
                                    self.nmea = self.data
                                    self.rover = GPGGADecodeAndUpdateRover(self.nmea, self.rover)
                            self.rfile.flush()

                        for s in writable:
                            if (time.time()-self.last_write_time)>=30:
                                self.logger_rh.info(str(self.client_address)+" - More than 30 second without sending nothing to user... It is alive?")
                                s.write("GeoMaxima NTRIP Caster\r\n")

                            if self.need_GGA and not self.got_GGA:
                                continue
                            else:
                                try:
                                    if self.path != conf['SETTINGS']['PREFERENCES']['STR_NEAREST_MOUNPOINT']:
                                        self.stream_data = self.db_rtcm_raw.find_one({'mountpoint': self.path})
                                    else:
                                        if self.rover.ref_station != None:
                                            self.logger_rh.debug("Nearest ref station: %s", self.rover.ref_station)
                                            self.stream_data = self.db_rtcm_raw.find_one({'mountpoint': self.rover.ref_station})
                                            self.logger_rh.debug("Stream data: %s", self.stream_data)
                                except Exception as e:
                                    self.logger_rh.info(str(self.client_address)+" - ERROR getting RTCM data from source: "+str(e))
                            
                            if self.stream_data:
                                ts = self.stream_data["timestamp"]
                                if ts != self.timestamp:
                                    self.stream_data_elapsed = time.time() - ts
                                    if self.stream_data_elapsed >= conf['SETTINGS']['PREFERENCES']['TIME_OUT_RAWDATA']:
                                        self.logger_rh.info(str(self.client_address)+" - Stream data outdated!! It seems no data available from source. Is it disconnected?")
                                        pass
                                    else:
                                        self.logger_rh.debug("Sending RTCM data to rover")
                                        s.write(self.stream_data["data"])
                                        try:
                                            self.db_rover_conn.update_one({'_id': self.rover._id },{"$set": {'timestamp_last_msg': ts, 'last_update':ts}})
                                            self.last_write_time = ts # set last timestamp
                                        except Exception as e:
                                            self.logger_rh.info(str(self.client_address)+" - ERROR saving nearest mountpoint info from user: "+str(e))
                                    self.timestamp = self.stream_data["timestamp"]

                        for s in exceptional:
                            self.logger_rh.info(str(self.client_address)+" - EXCEPTION in socket: "+self.client_address)

                    except socket.error as e:
                        err = e.args[0]
                        if err == errno.EAGAIN or err == errno.EWOULDBLOCK:
                            time.sleep(1)
                        if e.errno == errno.ECONNRESET:
                            self.logger_rh.info(str(self.client_address)+" - Errno 104 - Connection Reseted by Peer")
                            break
                        else:
                            self.logger_rh.info(str(self.client_address)+" - "+str(e))
                            break

                self.logger_rh.info(str(self.client_address)+" - Bye client.")
                self.rover.disconnectUser()
                
            else:
                self.logger_rh.info(str(self.client_address)+" - Not valid authentication from client. Send 401 Status")
                self.do_UNAUTHORIZED()
                
    def finish(self,*args,**kw):
        try:
            self.logger_rh.info(str(self.client_address)+" - Closing connection with client...")
        except Exception:
            pass
        try:
            if not self.wfile.closed:
                self.wfile.flush()
                self.wfile.close()
        except socket.error:
            pass
        try:
            if not self.rfile.closed:
                self.rfile.close()
        except Exception as e:
            pass

class CasterServer(SocketServer.ThreadingMixIn,
                   HTTPServer.HTTPServer):
    pass


def patch_broken_pipe_error():
    from socketserver import BaseServer
    from wsgiref import handlers

    handle_error = BaseServer.handle_error
    log_exception = handlers.BaseHandler.log_exception

    def is_broken_pipe_error():
        type, err, tb = sys.exc_info()
        return repr(err) == "error(32, 'Broken pipe')"

    def my_handle_error(self, request, client_address):
        if not is_broken_pipe_error():
            handle_error(self, request, client_address)

    def my_log_exception(self, exc_info):
        if not is_broken_pipe_error():
            log_exception(self, exc_info)

    BaseServer.handle_error = my_handle_error
    handlers.BaseHandler.log_exception = my_log_exception
patch_broken_pipe_error()


def setup_logging(
    default_path='geomaxima_logging_config.json',
    default_level=logging.INFO,
    env_key='LOG_CFG'):
    
    path = default_path
    value = os.getenv(env_key, None)
    if value:
        path = value
    if os.path.exists(path):
        with open(path, 'rt') as f:
            config = json.load(f)
        logging.config.dictConfig(config)
    else:
        logging.basicConfig(level=default_level)

#Main code
if __name__ == '__main__':
    if (platform.system() == "Windows"):
        os.system('cls')
    elif (platform.system() == "Linux"):
        os.system("clear")

    setup_logging()

    printCasterHeader()
    main_logger = logging.getLogger("CasterServer")
    main_logger.info("Version: %s", __version__)
    main_logger.info("Authors: %s", __author___)
    main_logger.info("License: %s", __license__)
    main_logger.info("Starting Caster Server...")
    
    main_logger.info("Trying to connect to MongoDB")
    try:
        db = getDatabase()
        db_rover_conn = db[conf['PROFILE']['DATABASE']['str_db_RoverConnections']]

        for doc in db_rover_conn.find({}):
            db_rover_conn.update_one(
                {"_id": doc["_id"]}, 
                { "$set": 
                    {
                        "conn_status": False
                    }
                },upsert = False)

    except Exception as e:
        main_logger.info("Oops! MongoDB seems not to be active. Exiting..."+str(e))
        main_logger.error("Oops! MongoDB seems not to be active. Exiting..."+str(e))
        sys.exit()

    httpd = CasterServer((conf['PROFILE']['IO']['CASTER_SERVER_HOST'], conf['PROFILE']['IO']['CASTER_SERVER_PORT']), CasterRequestHandler)

    main_logger.info ("Server %s started, listening in port: %s" % (conf['PROFILE']['IO']['CASTER_SERVER_HOST'], conf['PROFILE']['IO']['CASTER_SERVER_PORT']))
    main_logger.info ("Push Ctrl+C to stop it...")

    try:
        httpd.allow_reuse_address = True
        httpd.serve_forever()
    except KeyboardInterrupt:
        pass
    httpd.server_close()

    main_logger.info ("Server stopped - %s:%s" % (conf['PROFILE']['IO']['CASTER_SERVER_HOST'], conf['PROFILE']['IO']['CASTER_SERVER_PORT']))
    main_logger.info("Thanks for using GeoMaxima NTRIP Caster!")
