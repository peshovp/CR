# -*- coding: UTF-8 -*-

import os
import logging
import pymongo
import datetime
from datetime import datetime

logger = logging.getLogger(__name__)

_client = None
_conf = None

def _get_conf():
    """Lazy-load config to avoid circular imports."""
    global _conf
    if _conf is None:
        from config_load import Load_config
        _conf = Load_config()
    return _conf

def createMongoClient():
    """Return a singleton MongoClient instance.

    Tries environment variables first (MONGODB_HOST, MONGODB_PORT,
    MONGODB_USER, MONGODB_PASSWORD, MONGODB_DBNAME), falls back to
    config_file.ini via config_load.py.
    """
    global _client
    if _client is not None:
        return _client

    host = os.environ.get('MONGODB_HOST')
    port = int(os.environ.get('MONGODB_PORT', 27017))
    user = os.environ.get('MONGODB_USER')
    password = os.environ.get('MONGODB_PASSWORD')
    db_name = os.environ.get('MONGODB_DBNAME', 'geomaxima')

    if host and user and password:
        uri = 'mongodb://%s:%s@%s:%s/%s?authMechanism=SCRAM-SHA-256' % (
            user, password, host, port, db_name)
        _client = pymongo.MongoClient(uri, serverSelectionTimeoutMS=1)
    else:
        conf = _get_conf()
        uri = 'mongodb://%s:%s@%s:%s/%s?authMechanism=SCRAM-SHA-1' % (
            conf['PROFILE']['DATABASE']['STR_MONGODB_AUTH_USER'],
            conf['PROFILE']['DATABASE']['STR_MONGODB_AUTH_PASSWD'],
            conf['SETTINGS']['MONGODB']['HOST_MONGODB'],
            conf['SETTINGS']['MONGODB']['PORT_MONGODB'],
            conf['PROFILE']['DATABASE']['str_db_Name'])
        _client = pymongo.MongoClient(uri, serverSelectionTimeoutMS=1)

    _client.server_info()
    logger.info("MongoDB client created")
    return _client

def getDatabase():
    """Return the application database object from the singleton client."""
    client = createMongoClient()
    db_name = os.environ.get('MONGODB_DBNAME')
    if db_name:
        return client[db_name]
    conf = _get_conf()
    return client[conf['PROFILE']['DATABASE']['str_db_Name']]


def format_time(timestamp):
    date_time = datetime.fromtimestamp(timestamp)
    dt = date_time.strftime('%Y/%m/%d, %H:%M:%S')
    return dt



