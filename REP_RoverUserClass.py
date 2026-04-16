# -*- coding: UTF-8 -*-

import time
from bson.objectid import ObjectId
from config_load import Load_config
from general_defs import *

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
        print ("_id: "+ str(self._id))
        print ("conn_ip: "+ str(self.conn_ip))
        print ("conn_useragent: "+ str(self.conn_useragent))
        print ("conn_path: "+ str(self.conn_path))
        print ("username: "+ str(self.username))
        print ("login_time: "+ str(self.login_time))
        print ("timestamp_last_msg: "+ str(self.timestamp_last_msg))
        print ("distance_near: "+ str(self.distance_near))
        print ("coordinates: "+ str(self.coordinates))
        print ("ref_station: "+ str(self.ref_station))
        print ("sat_used: "+ str(self.sat_used))
        print ("nmea_msg: "+ str(self.nmea_msg))
        print ("conn_status: "+ str(self.conn_status))
            
    def newUser (self):
        dbClient = createMongoClient()
        db = dbClient[conf['PROFILE']['DATABASE']['str_db_Name']]
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
        dbClient.close()
        
        
    
    def disconnectUser (self):
        dbClient = createMongoClient()
        db = dbClient[conf['PROFILE']['DATABASE']['str_db_Name']]
        db_rover_conn = db[conf['PROFILE']['DATABASE']['str_db_RoverConnections']]
        db_rover_conn.update_one(
            {"_id": self._id}, 
            { "$set": 
                {
                    "conn_status": False
                }
            },upsert = False)
        dbClient.close()