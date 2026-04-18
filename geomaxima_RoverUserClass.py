# -*- coding: UTF-8 -*-

import logging
import time
from bson.objectid import ObjectId
from config_load import Load_config
from general_defs import getDatabase

logger = logging.getLogger(__name__)

conf=Load_config()

class RoverUser():

    def __init__(self, ip, useragent, path, username, timestamp):
        
        self._id = ObjectId()
        self.conn_ip = ip
        self.conn_useragent = useragent
        self.conn_path = path
        self.username = username
        self.login_time = timestamp
        self.timestamp_last_msg = None
        self.distance_near = None
        self.coordinates = None
        self.latency = None
        self.quality = None
        self.ref_station = None
        self.sat_used = None
        self.nmea_msg = None
        self.conn_status = True
        self.last_update = time.time()
        
        self.newUser()
            
    def printer (self):
        logger.debug("_id: %s", self._id)
        logger.debug("conn_ip: %s", self.conn_ip)
        logger.debug("conn_useragent: %s", self.conn_useragent)
        logger.debug("conn_path: %s", self.conn_path)
        logger.debug("username: %s", self.username)
        logger.debug("login_time: %s", self.login_time)
        logger.debug("timestamp_last_msg: %s", self.timestamp_last_msg)
        logger.debug("distance_near: %s", self.distance_near)
        logger.debug("coordinates: %s", self.coordinates)
        logger.debug("ref_station: %s", self.ref_station)
        logger.debug("sat_used: %s", self.sat_used)
        logger.debug("nmea_msg: %s", self.nmea_msg)
        logger.debug("conn_status: %s", self.conn_status)
            
    def newUser (self):
        db = getDatabase()
        db_rover_conn = db[conf['PROFILE']['DATABASE']['str_db_RoverConnections']]
        
        db_rover_conn.insert({
            '_id': self._id,
            'conn_status' : self.conn_status,
            'conn_ip' : self.conn_ip,
            'conn_useragent' : self.conn_useragent,
            'conn_path': self.conn_path,
            'username' : self.username,
            'login_time' : self.login_time, 
            'timestamp_last_msg' : self.timestamp_last_msg,
            'distance_near' : self.distance_near,
            'coordinates' : self.coordinates,
            'latency' : self.latency,
            'quality' : self.quality,
            'ref_station' : self.ref_station,
            'sat_used' : self.sat_used,
            'nmea_msg' : self.nmea_msg,
            'last_update' : self.last_update
            })
        
        
    
    def disconnectUser (self):
        db = getDatabase()
        db_rover_conn = db[conf['PROFILE']['DATABASE']['str_db_RoverConnections']]
        db_rover_conn.update_one(
            {"_id": self._id}, 
            { "$set": 
                {
                    "conn_status": False
                }
            },upsert = False)