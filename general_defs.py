# -*- coding: UTF-8 -*-

import pymongo
import datetime
from datetime import datetime
from config_load import Load_config

conf=Load_config()
def createMongoClient():
    maxServSelDelay = 1
    client = pymongo.MongoClient('mongodb://'+conf['PROFILE']['DATABASE']['STR_MONGODB_AUTH_USER']+':'+conf['PROFILE']['DATABASE']['STR_MONGODB_AUTH_PASSWD']+'@'+conf['SETTINGS']['MONGODB']['HOST_MONGODB']+':'+str(conf['SETTINGS']['MONGODB']['PORT_MONGODB'])+'/'+conf['PROFILE']['DATABASE']['str_db_Name']+'?authMechanism=SCRAM-SHA-1', serverSelectionTimeoutMS=maxServSelDelay)
    client.server_info()
    return client


def format_time(timestamp):
    date_time = datetime.fromtimestamp(timestamp)
    dt = date_time.strftime('%Y/%m/%d, %H:%M:%S')
    return dt



