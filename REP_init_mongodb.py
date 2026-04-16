# -*- coding: UTF-8 -*-
from REP_header_printer_p3 import *
import os
import sys
import platform
import codecs
import time
from config_load import Load_config
from general_defs import *

conf=Load_config()

if __name__ == '__main__':
    try:
        reload(sys)
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
    
    try:
        dbClient = createMongoClient()
        print("Se ha conectado a mongo")
        db = dbClient[conf['PROFILE']['DATABASE']['str_db_Name']]
        db_users = db[conf['PROFILE']['DATABASE']['str_db_UsersTable']]
        db_streams = db[conf['PROFILE']['DATABASE']['str_db_StreamsTable']]

        db_users.insert_one({
            "organisation" : "REP",
            "first_name" : "Red",
            "last_name" : "Extremeña de Posicionamiento",
            "zip_code" : "06006",
            "city" : "Badajoz",
            "country" : "ESP",
            "phone" : "+34600959393",
            "email" : "rep@unex.es",
            "description" : "Default Admin",
            "username" : "admin",
            "token_auth" : "YWRtaW46Y2FzdGVycmVw",
            "valid_from" : time.time(),
            "type" : 0.0,
            "active" : True
        })

        db_streams.insert_one({
            "id_station" : 0,
            "mountpoint" : conf['SETTINGS']['PREFERENCES']['STR_NEAREST_MOUNPOINT'],
            "identifier" : "NEAREST",
            "data_format" : "RTCM 3.1",
            "format_detail" : "1004(1), 1006(15), 1008(60), 1012(1), 1033(60)",
            "carrier" : 2,
            "nav_system" : "GPS+GLONASS",
            "network" : "CasterREP",
            "country" : "ESP",
            "latitude" : 0.0,
            "longitude" : 0.0,
            "nmea" : 0,
            "solution" : True,
            "generator" : "Caster REP",
            "compr_encryp" : "none",
            "authentication" : "B",
            "fee":"N",
            "bitrate" : 9600,
            "misc" : "null antenna",
            "encoder_pwd" : "gnss",
            "active" : True
        })

        dbClient.close() # Close MongoDB database connection
        
    except Exception as e:
        print ("Oops! MongoDB seems not to be active. Exiting..."+str(e))
        sys.exit()

