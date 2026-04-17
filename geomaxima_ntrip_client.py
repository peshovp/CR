# -*- coding: UTF-8 -*-
from config_file import *
from geomaxima_header_printer import printCasterHeader
from geomaxima_RTCM3_Decode import decodeRTCM3Packet
import socket
import pymongo
import time
import base64
import argparse
from bson.binary import Binary
import sys
import codecs
import os
import platform
import datetime
num_gps=0
num_glo=0
num_gal=0
num_bei=0

def timestamp():
	return datetime.datetime.fromtimestamp(time.time()).strftime('%Y-%m-%d %H:%M:%S')
def createMongoClient():
	maxServSelDelay = 1  
	
	client = pymongo.MongoClient('mongodb://'+STR_MONGODB_AUTH_USER+':'+STR_MONGODB_AUTH_PASSWD+'@'+HOST_MONGODB+':'+str(PORT_MONGODB)+'/'+str_db_Name+'?authMechanism=SCRAM-SHA-1',
										 serverSelectionTimeoutMS=maxServSelDelay)
	
	client.server_info() 
	return client
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
	
	parser = argparse.ArgumentParser()
	parser.add_argument("ip",type = str, help = "Remote caster connection IP/Host")
	parser.add_argument("port",type = str, help = "Remote caster connection port")
	parser.add_argument("-c", "--caster",action = "store_true", help="Connection will be to a NTRIP caster")
	parser.add_argument("-m","--mountpoint",type = str, help="NTRIP caster mountpoint")
	parser.add_argument("-u","--user",type = str, help = "NTRIP caster user")
	parser.add_argument("-p","--password",type = str, help = "NTRIP caster password")
	parser.add_argument("-s", "--station",action = "store_true", help = "Connection will be to an station which operate as a server")
	args = parser.parse_args()
	
	if args.caster and (args.caster == None or args.mountpoint == None or args.password == None):
		sys.exit()
	
	ip = args.ip
	port=int(args.port)
	if not args.station and not args.caster:
		sys.exit()
	if args.station and args.caster:
		sys.exit()
	
	if args.caster:
		mountpoint = args.mountpoint
		user = args.user
		passw = args.password
	
	if args.station:
		if args.mountpoint != None:
			mountpoint = args.mountpoint
		else:
			sys.exit()
	
	try:
		client = createMongoClient()
		db = client['casterrep']  
		rtcm_raw = db['rtcm_raw']  
		streams = db['streams']  
	except Exception as e:
		sys.exit() 
	
	stream = streams.find_one({'mountpoint': mountpoint})
	
	if stream == None:
		db.close()
		sys.exit()
	
	s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
	s.connect((ip,port))
	requested = False
	
	while 1:
		try:
			if args.caster:
				
				
				if not requested:
					
					token = base64.encodestring('%s:%s' % (user,passw)).strip()
					
					message = "GET /"+mountpoint+" HTTP/1.0\r\nUser-Agent: NTRIP RepNtripClient/1.00\r\nAuthorization: Basic "+token+"\r\nConnection: close\r\n\r\n"
					
					s.sendall(message)
					requested = True
				try:
					
					data = s.recv(16000)
					
					if (data.find("Unauthorized"))!=-1:
						break
					
					decod = decodeRTCM3Packet(data)

					rtcm_raw=db.rtcm_raw

					for doc in rtcm_raw.find({"mountpoint":mountpoint},{"mountpoint":1,"timestamp":1,"n_gps":1,"n_glo":1,"n_gal":1,"n_bei":1}):
					    if decod[1]!=-999: 
							num_gps=decod[1]
						else:
							num_gps=doc["n_gps"]
						if decod[2]!=-999: 
							num_glo=decod[2]
						else:
							num_glo=doc["n_glo"]
						if decod[3]!=-999: 
							num_gal=decod[3]
						else:
							num_gal=doc["n_gal"]
						if decod[4]!=-999: 
							num_bei=decod[4]
						else:
							num_bei=doc["n_bei"]
					rtcm_raw.update(
						{"mountpoint": mountpoint},
						{"$set":
							{
								"data": Binary(data), "n_gps": str(num_gps), "n_glo": str(num_glo), "n_gal": str(num_gal), "n_bei": str(num_bei), "timestamp": time.time(), "id_station": decod[0]}
						},upsert=True)
					
				except Exception as e:
					print ("Exception getting data from socket: "+str(e))
			elif args.station:
				
				try:
					
					datos = s.recv(16000)
					
					decod=decodeRTCM3Packet(datos)
					
					rtcm_raw=db.rtcm_raw

					for doc in rtcm_raw.find({"mountpoint":mountpoint},{"mountpoint":1,"timestamp":1,"n_gps":1,"n_glo":1,"n_gal":1,"n_bei":1}):
					    if decod[1]!=-999: 
							num_gps=decod[1]
						else:
							num_gps=doc["n_gps"]
						if decod[2]!=-999: 
							num_glo=decod[2]
						else:
							num_glo=doc["n_glo"]
						if decod[3]!=-999: 
							num_gal=decod[3]
						else:
							num_gal=doc["n_gal"]
						if decod[4]!=-999: 
							num_bei=decod[4]
						else:
							num_bei=doc["n_bei"]

					rtcm_raw.update(
						{"mountpoint": mountpoint},
						{"$set":
							{
								"data": Binary(datos), "n_gps": str(num_gps), "n_glo": str(num_glo), "n_gal": str(num_gal), "n_bei": str(num_bei), "timestamp": time.time(), "id_station": decod[0]}
						},upsert=True)
				except Exception as e:
					print ("Exception getting data from socket: "+str(e))
		except:
			print ("Unexpected error: ", sys.exc_info()[1])
			break
	
	
	s.close()
	db.close()