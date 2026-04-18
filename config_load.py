# -*- coding: UTF-8 -*-

import logging
import os
import sys
import importlib
import json
from configobj import ConfigObj

logger = logging.getLogger(__name__)


def Load_config():

        config_INI=json.loads(open('config_var.json').read())
        logger.debug("Config INI loaded: %s", config_INI)
        logger.debug("Finished reading config file")
        for varConfig in config_INI:
            FILE_CONFIG_INI=varConfig['FILE_CONFIG_INI']
        if os.path.isfile(FILE_CONFIG_INI):
           conf=ConfigObj(FILE_CONFIG_INI, unrepr=True, configspec='configspec', default_encoding='utf8')
           logger.debug("Configuration: %s", conf)
           return (conf)
        else:
           logger.error("INI config file does not exist.")
           sys.exit()

Load_config()