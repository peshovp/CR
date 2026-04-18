# -*- coding: UTF-8 -*-
import logging

logger = logging.getLogger(__name__)

def decodeRTCM3Packet(raw):
    PREAMBLE='d3'
    rtcm_hex=raw.hex()


    msg_num_header=0
    cont_sat=0
    id_station = 0
    indice_preamble = 0
    n_gps = -999
    n_glo = -999
    n_gal = -999
    n_bei = -999
    n_sbas= -999
    n_jap = -999    	

    while indice_preamble < len(rtcm_hex):
        indice_preamble = rtcm_hex.find(PREAMBLE, indice_preamble)
        if indice_preamble == -1:
            break
        try:
            rtcm_bin = ''.join(bin(int(digitoHEX, 16))[2:].zfill(4) for digitoHEX in rtcm_hex[indice_preamble:])
            #print('El rtcm: ')
            #print(rtcm_bin)
            indice_mensaje_header = 8 + 6 + 10
            msg_num_header = int(rtcm_bin[indice_mensaje_header:indice_mensaje_header + 12], 2)
            #print('mensaje cabecera: ')
            #print(msg_num_header)
            msg_id = int(rtcm_bin[15:24], 2)
            #print('id: ')
            #print(msg_id)
            
            if msg_num_header == 1001 or msg_num_header == 1002 or msg_num_header == 1003 or msg_num_header == 1004:
                logger.debug("RTCM message header: %s", msg_num_header)
                indice_nsat = indice_mensaje_header + 12 + 12 + 30 + 1
                indice_idstation = indice_mensaje_header + 12
                id_station = int(rtcm_bin[indice_idstation:indice_idstation + 12], 2)
                n_gps = int(rtcm_bin[indice_nsat:indice_nsat + 5], 2)
                
            if msg_num_header == 1009 or msg_num_header == 1010 or msg_num_header == 1011 or msg_num_header == 1012:
                indice_nsat = indice_mensaje_header + 12 + 12 + 27 + 1
                indice_idstation = indice_mensaje_header + 12
                id_station = int(rtcm_bin[indice_idstation:indice_idstation + 12], 2)
                n_glo = int(rtcm_bin[indice_nsat:indice_nsat + 5], 2)

            if (msg_num_header>=1071 and msg_num_header<=1077) or (msg_num_header>=1081 and msg_num_header<=1087) or (msg_num_header>=1091 and msg_num_header<=1097) or (msg_num_header>=1121 and msg_num_header<=1127):
                indice_idstation = indice_mensaje_header + 12
                id_station = int(rtcm_bin[indice_idstation:indice_idstation + 12], 2)               
                indice_nsat = indice_mensaje_header + 12 + 12 + 30+ 1 + 3 + 7 + 2+ 2 + 1 + 3
                cont_sat=0
                for i in range(1,64):
                    #print(rtcm_bin[indice_nsat:indice_nsat+i+1])
                    n_sat = int(rtcm_bin[indice_nsat+i:indice_nsat+i+1], 2)
                    #print(int(rtcm_bin[indice_nsat+i:indice_nsat+i+1], 2))
                    if n_sat==1:
                        #print('Entra en n_sat')
                        cont_sat=cont_sat+1
                        #print(cont_sat)
                
                if msg_num_header>=1071 and msg_num_header<=1077:
                    #print('entra en gps')
                    #print('con estos satelites')
                    #print(cont_sat)
                    n_gps=cont_sat
                    #print('con estos satelites en n_gps')
                    #print(n_gps)
                if msg_num_header>=1081 and msg_num_header<=1087:
                    n_glo=cont_sat
                if msg_num_header>=1091 and msg_num_header<=1097:
                    n_gal=cont_sat
                if msg_num_header>=1121 and msg_num_header<=1127:
                    n_bei=cont_sat

        except (ValueError, IndexError) as e:
            logger.warning("Failed to decode RTCM packet: not enough bits: %s", e)
        indice_preamble += 2
        #print('para terminar')
        #print(n_gps)
    return id_station, n_gps, n_glo, n_gal, n_bei



