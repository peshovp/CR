# -*- coding: UTF-8 -*-

import os
import sys
import importlib
import json
from configobj import ConfigObj


def Load_config():

        config_INI=json.loads(open('config_var.json').read())
        print(config_INI)
        print('termina el fo')
        for varConfig in config_INI:
            FILE_CONFIG_INI=varConfig['FILE_CONFIG_INI']
        if os.path.isfile(FILE_CONFIG_INI):
           conf=ConfigObj(FILE_CONFIG_INI, unrepr=True, configspec='configspec', default_encoding='utf8')
           print (conf)
           return (conf)
        else:
           print ('Fichero INI no existe.')
           sys.exit()

Load_config()