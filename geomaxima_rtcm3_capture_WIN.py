# -*- coding: iso-8859-1 -*-
from geomaxima_config_file import *
from geomaxima_header_printer import printCasterHeader
from geomaxima_RTCM3_Decode import decodeRTCM3Packet

import time  
import platform
import codecs
import multiprocessing
import socket
import sys
import json
import logging
import logging.config
from logging.handlers import RotatingFileHandler
import os
import pymongo
from bson.binary import Binary
import socket
import threading
import SocketServer

class ThreadedTCPRequestHandler(SocketServer.BaseRequestHandler):
    def handle(self):
        self.name = str(self.request.getpeername())
        main_logger.info(self.name+" - New source connected")
        
        try:
            client = createMongoClient()
            db = client['casterrep']  
            rtcm_raw = db['rtcm_raw']  
            streams = db['streams']  
        except Exception as e:
            main_logger.info(self.name+" - Oops! MongoDB seems not to be active. Exiting..."+str(e))
            main_logger.error(self.name+" - Oops! MongoDB seems not to be active. Exiting..."+str(e))
            sys.exit()        
       
        mountp = ""
        
        while True:
            try:
                data = self.request.recv(1024)
                
            except Exception as e:
                main_logger.info(self.name+" - ERROR receiving TCP packet. It seems that the client has disconnected: "+str(e))
                break
            
            if data == "":
                main_logger.info(self.name+" - Client sent empty data. Is the socket closed remotely?")
                break
            
            if data.find('SOURCE') >- 1:
                
                rcvd = data.replace("\r\n", " ")
                packet_splitted = str(rcvd).split(' ')
                
                
                if packet_splitted[0] == "SOURCE":
                    encoder = packet_splitted[1]
                    mountp = packet_splitted[2].replace("/", "")
                    try:
                        
                        stream = streams.find_one({'mountpoint': mountp})
                        
                        if stream == None:
                            main_logger.info(self.name+" - Mountpoint does not exist in database: ERROR - Mount Point Invalid")
                            self.request.sendall("ERROR - Mount Point Invalid\r\n")
                        
                        elif stream['active'] == False:
                            main_logger.info(self.name+" - Mountpoint is not active in database: ERROR - Mount Point Invalid")
                            self.request.sendall("ERROR - Mount Point Invalid\r\n")
                        
                        elif stream['encoder_pwd'] == encoder:
                            main_logger.info(self.name+" - Enconder password is correct: ICY 200 OK")
                            self.request.sendall("ICY 200 OK\r\n")
                        
                        
                    except Exception as e:
                        main_logger.info(self.name+" - ERROR when manage SOURCE request: "+ str(e))
            elif mountp != "":
                id_station, n_gps, n_glo = decodeRTCM3Packet(data)
                print self.name+" - "+mountp+" RTCM3 packet summary ~~> ID Station:"+ str(id_station)+ " GPS:"+ str(n_gps)+ " GLO:"+ str(n_glo)
                
                stream = streams.find_one({'mountpoint': mountp, 'id_station': id_station})
                
                if stream == None:
                    main_logger.info(self.name+" - Mountpoint and id station are not the same in database. Discard packet...")
                else:
                    
                    rtcm_raw.update(
                        {"mountpoint": mountp}, 
                        { "$set": 
                        {"data": Binary(data), "n_gps": n_gps, "n_glo": n_glo, "timestamp": time.time(), "id_station": id_station}
                        },upsert = True)
            else:
                main_logger.info(self.name+" - The client seems not be a valid source. Any SOURCE msg found.")
                main_logger.info(self.name+" - Found: ->"+str(data))
                self.request.sendall("Bye.\r\n")
                break
        main_logger.info(self.name+" - Disconnecting source...")
class ThreadedTCPServer(SocketServer.ThreadingMixIn, SocketServer.TCPServer):
    pass
def createMongoClient():
    maxServSelDelay = 1  
    
    client = pymongo.MongoClient('mongodb://'+MONGODB_AUTH_USER+':'+MONGODB_AUTH_PASSWD+'@'+MONGODB_HOST_PORT+'/casterrep?authMechanism=SCRAM-SHA-1',
                                         serverSelectionTimeoutMS=maxServSelDelay)
    client.server_info() 
    return client
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
if __name__ == "__main__":
    try:
        if (platform.system() == "Windows"):
            cls = lambda: os.system('cls')
            cls()
        elif (platform.system() == "Linux"):
            os.system("clear") 
    except Exception as e:
        pass
    
    printCasterHeader() 
    setup_logging() 
    
    
    sizelog = os.stat('rtcm3_capture.log')
    if sizelog.st_size > 10000000:
        open('rtcm3_capture.log','w').close()
    main_logger = logging.getLogger("RTCM3Capture") 
    main_logger.info("Starting RTCM3 Capture Server for Windows...") 
    
    server = ThreadedTCPServer((RTCM_CAPTURE_SERVER_HOST, RTCM_CAPTURE_SERVER_PORT), ThreadedTCPRequestHandler)
    ip, port = server.server_address
    
    server_thread = threading.Thread(target=server.serve_forever, name="RTCM3Capture")
    
    server_thread.daemon = True
    server_thread.allow_reuse_address = True
    server_thread.start()
    main_logger.info ("Server %s started, listening on port: %s" % (RTCM_CAPTURE_SERVER_HOST, RTCM_CAPTURE_SERVER_PORT))
    try:
        while threading.active_count() >= 1:
            pass
    except KeyboardInterrupt:
        main_logger.info("Server stopped by Keyboard Interrupt")
    server.shutdown()
    server.server_close()
    
    main_logger.info ("Server stopped - %s:%s" % (RTCM_CAPTURE_SERVER_HOST, RTCM_CAPTURE_SERVER_PORT))
    main_logger.info("Thanks for using Caster REP 2.0!")
