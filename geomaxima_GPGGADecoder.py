# -*- coding: UTF-8 -*-
from math import radians, cos, sin, asin, sqrt
import logging
import time

from config_load import Load_config
from general_defs import *

logger = logging.getLogger(__name__)

conf=Load_config()


def GPGGADecodeAndUpdateRover (nmea_gga, rover):
    nmea_gga = str(nmea_gga)
    start = nmea_gga.find('$GPGGA')
    if start == -1:
        start = nmea_gga.find('$GNGGA')
    if start == -1:
        start = nmea_gga.find('$GLGGA')
    end = nmea_gga.find('\r\n')
    splitted_gga = nmea_gga[start:end].split(',')

    try:
        lat_char = splitted_gga[3]
        lon_char = splitted_gga[5]
        latitude = splitted_gga[2] if lat_char == "N" else splitted_gga[2]
        longitude = "-"+str(splitted_gga[4]) if lon_char == "E" else splitted_gga[4]
        
        lat2 = float(splitted_gga[2][2:11]) / 60 
        lon2 = float(splitted_gga[4][3:11]) / 60
        
        if lat_char == "N":
            latitude = float(int(splitted_gga[2][:2]))+lat2
        elif lat_char == "S":
            latitude = - (float(int(splitted_gga[2][:2])) + lat2)
        
        if lon_char == "E":
            longitude = float(int(splitted_gga[4][:3]))+lon2
        elif lon_char == "W":
            longitude = - (float(int(splitted_gga[4][:3]))+lon2)
        
    except Exception as e:
        logger.error("Failed to get position coordinates from NMEA message: %s", e)
    
    num_quality = splitted_gga[6]
    q = { 0: 'Invalid', 1: 'GPS Fix', 2: 'DGPS', 3: 'GPS PPS Mode', 4: 'RTK', 5: 'Float RTK', 6: 'Estimated', 7: 'Manual Input Mode', 8: 'Simulator Mode' }
    quality = q[int(num_quality)]

    
    sat_used = splitted_gga[7]
    hdop = splitted_gga[8]
    latency = splitted_gga[13]
    if latency == '':
        latency = None
    
    distance, mountp = getNearestMountpoint(latitude, longitude)
    
    dbClient = createMongoClient()
    db = dbClient[conf['PROFILE']['DATABASE']['str_db_Name']]
    db_rover_conn = db[conf['PROFILE']['DATABASE']['str_db_RoverConnections']]
    db_rover_conn.update_one({'_id': rover._id },{"$set": {
        'sat_used' : sat_used, 
        'latency': latency, 
        'quality': quality, 
        'coordinates': (latitude, longitude),
        'last_update' : time.time(),
        'distance_near': distance,
        'ref_station': mountp,
        'nmea_msg': nmea_gga}})
    dbClient.close()

    rover.sat_used = sat_used
    rover.latency = latency
    rover.coordinates = (latitude, longitude)
    rover.last_update = time.time()
    rover.distance_near = distance
    rover.ref_station = mountp
    rover.nmea_msg = nmea_gga

    return rover

    

def haversine(lon1, lat1, lon2, lat2):
    lon1, lat1, lon2, lat2 = map(radians, [lon1, lat1, lon2, lat2])
    dlon = lon2 - lon1 
    dlat = lat2 - lat1 
    a = sin(dlat/2)**2 + cos(lat1) * cos(lat2) * sin(dlon/2)**2
    c = 2 * asin(sqrt(a)) 
    km = 6367 * c
    return km
    
    
    
def getNearestMountpoint(user_lat, user_lon):
    distance_mountpoint_array = []
    try:
        dbClient = createMongoClient()
        db = dbClient[conf['PROFILE']['DATABASE']['str_db_Name']]
        db_rtcm_raw = db[conf['PROFILE']['DATABASE']['str_db_RTCMTable']]
        db_streams = db[conf['PROFILE']['DATABASE']['str_db_StreamsTable']]

        streams_data = db_streams.find({})  
        
        for stream in streams_data:
            if stream['solution'] == False:
                rtcm_raw_data = db_rtcm_raw.find_one({'mountpoint': stream['mountpoint']})
                if ((time.time() - rtcm_raw_data['timestamp']) < conf['SETTINGS']['PREFERENCES']['TIME_OUT_RAWDATA']):
                    lat = float(stream['latitude'])
                    lon = float(stream['longitude'])
                    mountp = stream['mountpoint']
                    distance = haversine(float(user_lon),float(user_lat),lon,lat)
                    distance_mountpoint_array.append((float("{:.3f}".format(distance)), mountp))
        
        dbClient.close()
        distance_mountpoint_array = sorted(distance_mountpoint_array, key=lambda x: x[0], reverse=False) 
        
        return distance_mountpoint_array[0]
    
    except Exception as e:
        logger.error("Failed to get nearest mountpoint: %s", e)
        return (None,None)
