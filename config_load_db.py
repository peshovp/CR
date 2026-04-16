# -*- coding: UTF-8 -*-
import os
import sys
import importlib
import json
from configobj import ConfigObj
import pymongo
from pymongo import MongoClient

def Load_config():

        con = MongoClient('localhost',27017)
        db = con.casterrep
 
        
        configuracion = db.conf
 
        resultado = configuracion.find_one()
 
        
        return (resultado)