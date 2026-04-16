# -*- coding: UTF-8 -*-

__author___ = "Juan Morillo, Javier Guerrero, Ruben Molina, Domingo Solomando"
__version__ = "v3.0.20201010"
__license__ = "GNU General Public License (GPL-3.0-only or GPL-3.0-or-later)"
__description__ = "Caster Server: This script captures rtcm raw data from CORS"

from REP_config_file import *
from REP_header_printer import printCasterHeader
from REP_RTCM3_Decode import decodeRTCM3Packet
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


def createMongoClient():
    maxServSelDelay = 1
    client = pymongo.MongoClient('mongodb://'+MONGODB_AUTH_USER+':'+MONGODB_AUTH_PASSWD+'@'+MONGODB_HOST_PORT+'/casterrep?authMechanism=SCRAM-SHA-1',
                                         serverSelectionTimeoutMS=maxServSelDelay)
    client.server_info() 
    return client


def create_rotating_log(path, address):
        logger = logging.getLogger("Process %r" %(str(address)))
        logger.setLevel(logging.INFO)
        handler = RotatingFileHandler(path, maxBytes=1000000, backupCount=5)
        formatter = logging.Formatter('%(asctime)s - %(name)s - %(message)s')
        handler.setFormatter(formatter)
        logger.addHandler(handler)
        return logger


def handle(connection, address):
    logger = create_rotating_log('./rtcm3_capture_processes.log',address)
    try:
        client = createMongoClient()
        db = client['casterrep']
        rtcm_raw = db['rtcm_raw']
        streams = db['streams']
    except Exception as e:
        main_logger.info("Oops! MongoDB seems not to be active. Exiting..."+str(e))
        sys.exit() 
    mountp = ""

    try:
        logger.info("New client connected")
        bandera = True
        while True:
            try:
                data = connection.recv(16 * 1024)
            except Exception as e:
                logger.info("ERROR receiving tcp packet...")
                break
            except KeyboardInterrupt:
                break

            if data == "":
                logger.info("Socket closed remotely")
                break

            if bandera == True:
            	dataString = data.decode("ascii")
            if dataString.find('SOURCE') > -1:
                rcvd = dataString.replace("\r\n", " ")
                print(rcvd)
                bandera = False
                dataString = ''
                packet_splitted = rcvd.split(' ')
                if packet_splitted[0] == "SOURCE":
                    encoder = packet_splitted[1]
                    mountp = packet_splitted[2].replace("/", "")

                    try:
                        stream = streams.find_one({'mountpoint': mountp})
                        if stream == None:
                            logger.info('Mountpoint does not exist in database: ERROR - Mount Point Invalid')
                            connection.sendall("ERROR - Mount Point Invalid\r\n")

                        elif stream['active'] == False:
                            logger.info('Mountpoint is not active in database: ERROR - Mount Point Invalid')
                            connection.sendall("ERROR - Mount Point Invalid\r\n")

                        elif stream['encoder_pwd'] == encoder:
                            logger.info('Enconder password is correct: ICY 200 OK')
                            connection.sendall(b"ICY 200 OK\r\n")
                    except Exception as e:
                        logger.info("ERROR when manage SOURCE request: "+ str(e))

            elif mountp != "":
                id_station, n_gps, n_glo, n_gal, n_bei = decodeRTCM3Packet(data)
                logger.info(mountp+" RTCM3 packet summary ~~> ID Station:"+ str(id_station)+ " GPS:"+ str(n_gps)+ " GLO:"+ str(n_glo)+ " GAL:"+ str(n_gal)+ " BEI:"+ str(n_bei))

                stream = streams.find_one({'mountpoint': mountp, 'id_station': id_station})

                if stream == None:
                    logger.info('Mountpoint and id station are not the same in database. Discard packet...')
                else:
                    for doc in rtcm_raw.find({"mountpoint":mountp},{"mountpoint":1,"timestamp":1,"n_gps":1,"n_glo":1,"n_gal":1,"n_bei":1}):
                        if n_gps!=-999:
                            num_gps=n_gps
                        else:
                            num_gps=doc["n_gps"]
                        if n_glo!=-999:
                            num_glo=n_glo
                        else:
                            num_glo=doc["n_glo"]
                        if n_gal!=-999:
                            num_gal=n_gal
                        else:
                            num_gal=0
                        if n_bei!=-999:
                            num_bei=n_bei
                        else:
                            num_bei=0
                        
                        rtcm_raw.update(
                        {"mountpoint": mountp},
						{"$set":
							{"data": Binary(data), "n_gps": str(num_gps), "n_glo": str(num_glo), "n_gal": str(num_gal), "n_bei": str(num_bei), "timestamp": time.time(), "id_station": id_station}
						},upsert=True)
 
            else:
                logger.info("The client seems not be a valid source. Any SOURCE msg found.")
                logger.info("Found: ->"+str(data))
                connection.sendall(b"Bye.\r\n")
                break

    except:
        logger.exception("Problem handling request")
    finally:
        logger.info("Closing socket")
        connection.close()
        client.close()

class Server(object):
    def __init__(self, hostname, port, logger):
        self.hostname = hostname
        self.port = port
        self.logger = logger

    def start(self):
        self.logger.info("Listening...")
        self.logger.info ("Push Ctrl+C to stop it...")
        self.socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        self.socket.bind((self.hostname, self.port))
        self.socket.listen(1)
        while True:
            try:
                conn, address = self.socket.accept()
                self.logger.info("Got connection from %r"%(str(address)))
                process = multiprocessing.Process(target=handle, args=(conn, address))
                process.daemon = True
                process.start()
                self.logger.info("%r", process)
            except Exception as e:
                self.logger.info("EXCEPTION:" +str(e))
                


def setup_logging(
    default_path='REP_logging_config.json',
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

#Comienza el código principal

if __name__ == '__main__':
    try:
        reload(sys) #Indicamos que la codificacion se haga en utf-8
        sys.stdout = codecs.getwriter('utf8')(sys.stdout)
        sys.stderr = codecs.getwriter('utf8')(sys.stderr)
        sys.setdefaultencoding('utf-8')

        if (platform.system() == "Windows"):
            cls = lambda: os.system('cls')
            cls()
        elif (platform.system() == "Linux"):
            os.system("clear")

    except Exception as e:
        pass
    
    printCasterHeader()
    print ("  - Version: "+__version__)
    print ("  - Authors: "+__author___)
    print ("  - License: "+__license__+"\n")
    
    setup_logging()
    main_logger = logging.getLogger("RTCM3Capture")
    main_logger.info("Starting RTCM3 Capture Server...")
    server = Server(RTCM_CAPTURE_SERVER_HOST, RTCM_CAPTURE_SERVER_PORT, main_logger)
    try:
        main_logger.info ("Server %s started, listening on port: %s" % (RTCM_CAPTURE_SERVER_HOST, RTCM_CAPTURE_SERVER_PORT))
        try:
            server.start()
        except KeyboardInterrupt:
            main_logger.info("Server stopped by Keyboard Interrupt")
    except:
        main_logger.exception("Unexpected exception")
    finally:
        main_logger.info("Exiting...")
        if multiprocessing.active_children():
            main_logger.info("Wait...there is a subprocess active")
            for process in multiprocessing.active_children():
                main_logger.info("Shutting down the process %r", process)
                process.terminate()
                process.join()
    main_logger.info ("Server stopped - %s:%s" % (RTCM_CAPTURE_SERVER_HOST, RTCM_CAPTURE_SERVER_PORT))
    main_logger.info("Thanks for using Caster REP 2.0!")
