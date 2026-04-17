# -*- coding: UTF-8 -*-
from geomaxima_header_printer import *
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

        default_password = 'CHANGE_ME_ON_FIRST_RUN'
        default_user = 'admin'
        # Generate token_auth with: base64(username:password)
        import base64
        token_auth = base64.b64encode(f"{default_user}:{default_password}".encode()).decode()

        db_users.insert_one({
            "organisation" : "",
            "first_name" : "Admin",
            "last_name" : "",
            "zip_code" : "",
            "city" : "",
            "country" : "",
            "phone" : "",
            "email" : "",
            "description" : "Default Admin",
            "username" : default_user,
            "token_auth" : token_auth,
            "valid_from" : time.time(),
            "type" : 0.0,
            "active" : True
        })

        print("WARNING: Change the default admin password immediately!")

        db_streams.insert_one({
            "id_station" : 0,
            "mountpoint" : conf['SETTINGS']['PREFERENCES']['STR_NEAREST_MOUNPOINT'],
            "identifier" : "NEAREST",
            "data_format" : "RTCM 3.1",
            "format_detail" : "1004(1), 1006(15), 1008(60), 1012(1), 1033(60)",
            "carrier" : 2,
            "nav_system" : "GPS+GLONASS",
            "network" : "GeoMaxima",
            "country" : "ESP",
            "latitude" : 0.0,
            "longitude" : 0.0,
            "nmea" : 0,
            "solution" : True,
            "generator" : "GeoMaxima NTRIP Caster",
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

