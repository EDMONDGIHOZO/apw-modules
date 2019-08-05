#! /usr/bin/python

# To change this license header, choose License Headers in Project Properties.
# To change this template file, choose Tools | Templates
# and open the template in the editor.

import MySQLdb
from datetime import datetime
from urllib import quote
import fnmatch
import os

__author__="kinyua"
__date__ ="$Jun 3, 2015 3:28:30 PM$"

if __name__ == "__main__":
    
    #first get all the download files
    local_conn = MySQLdb.connect (host='localhost',
                            user='aidspandev',
                            passwd='aIdspan008!',
                            db='apw2_production', charset='utf8',
                            use_unicode=True,)

    cursor = local_conn.cursor()
        
    #now insert the granular data into the statistic table
    cursor.execute("SELECT filename, filepath FROM d_file GROUP BY filename, filepath ORDER BY d_date ASC")
    files = cursor.fetchall()
    
    for file in files:
        
        cursor.execute("INSERT INTO publication_download (filepath, filename) VALUES ('" + file[1] + "', '" + file[0] + "')")
        
        cursor.execute("SELECT d_date, count(id) FROM d_file WHERE filename = '" + file[0] + "' AND filepath = '" + file[1] + "' GROUP BY d_date")
        
        stats = cursor.fetchall()
        
        for stat in stats:
            sql = "INSERT INTO publication_download_statistic (publication_download_id, download_date, download_count) VALUES ((SELECT id FROM publication_download WHERE filepath = '" + file[1] + "' AND filename = '" + file[0] + "'), '" + stat[0].strftime("%Y-%m-%d") + "', " + str(stat[1]) + ")"
            cursor.execute(sql)    
                    
    cursor.close()
    
    local_conn.commit()
    local_conn.close()
