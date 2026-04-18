# -*- coding: UTF-8 -*-
from geomaxima_header_printer import *
import logging
import os
import sys
import platform
import codecs
import time
from config_load import Load_config
from general_defs import getDatabase

logger = logging.getLogger(__name__)

conf=Load_config()

if __name__ == '__main__':
    if (platform.system() == "Windows"):
        os.system('cls')
    elif (platform.system() == "Linux"):
        os.system("clear")

    printCasterHeader()
    
    try:
        db = getDatabase()
        logger.info("Connected to MongoDB")
        db_users = db[conf['PROFILE']['DATABASE']['str_db_UsersTable']]
        db_streams = db[conf['PROFILE']['DATABASE']['str_db_StreamsTable']]

        default_password = 'CHANGE_ME_ON_FIRST_RUN'
        default_user = 'admin'
        # Hash password with bcrypt
        import bcrypt
        password_hash = bcrypt.hashpw(default_password.encode('utf-8'), bcrypt.gensalt()).decode('utf-8')

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
            "password_hash" : password_hash,
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
            "encoder_pwd" : "CHANGE_ME",
            "active" : True
        })

    except Exception as e:
        logger.error("MongoDB seems not to be active. Exiting: %s", e)
        sys.exit()

