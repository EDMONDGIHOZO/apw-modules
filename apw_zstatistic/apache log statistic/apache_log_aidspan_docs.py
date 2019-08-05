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
    
    app_main_path = '/extdisks/devworld/nerdlab/php-labs/apw/'
    
    doc_full_file_paths = []
    doc_file_names = []
    
    for root, dirnames, filenames in os.walk(app_main_path + 'sites/default/files/ddocs'):
        for filename in filenames:
            if filename.find(".pdf") > 0 or filename.find(".doc") > 0:
                full_path = os.path.join(root, filename)
                relative_path = full_path.replace(app_main_path, '')
                
                doc_full_file_paths.append(relative_path)
                doc_file_names.append(filename)
    
    index = 0
    
    for doc_file_name in doc_file_names:

        filepath = doc_full_file_paths[index]
        index += 1
        
        search_term = quote(doc_file_name) + ' HTTP/1.1" 20'

        with open(app_main_path + '/logs/access.log', "r") as fp:
            for line in fp:
                
                if search_term in line:
                    d = line[(line.index('[') + 1):]
                    d_date = d[:11]

                    date_object = datetime.strptime(d_date, '%d/%b/%Y')
                    
                    #insert into the db
                    sql = "INSERT INTO d_file (filename, filepath, d_date) VALUES ('" + doc_file_name + "', '" + filepath + "', '" + date_object.strftime("%Y-%m-%d") + "');"
                    cursor.execute(sql)

        print "Done with " + doc_file_name
        
    cursor.close()
    
    local_conn.commit()
    local_conn.close()
