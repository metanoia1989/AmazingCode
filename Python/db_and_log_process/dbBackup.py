# -*- coding: UTF-8 -*-
 
import MySQLdb
import time
import datetime
import os
import smtplib
from email.mime.text import MIMEText
 
#抓取MM
class MetBackup:
 
    #页面初始化
    def __init__(self):
        self.sqlDB = 'dbname'
        self.dbUser='root'
        self.dbPasswd='xxxxxx'
        self.dbHost='127.0.0.1'
        self.dbCharset = 'utf8'
        self.bkPath = '/alidata/log/mysql/met_' + datetime.datetime.now().strftime('%Y%m%d')+'.sql'


 
    #获取索引页面的内容
    def backupDB(self):
        #查出MySQL中所有的数据库名称
        try:
            print 'The database backup to start! %s'   %time.strftime('%Y-%m-%d %H:%M:%S')
            
            print "mysqldump -h%s -u%s -p%s %s --default_character-set=%s | gzip > %s.gz" %(self.dbHost,self.dbUser,self.dbPasswd,self.sqlDB,self.dbCharset,self.bkPath)

            if os.path.exists(self.bkPath):
                    os.remove(self.bkPath)
            os.system("/alidata/server/mysql/bin/mysqldump -h%s -u%s -p%s %s --default_character-set=%s | gzip > %s.gz" %(self.dbHost,self.dbUser,self.dbPasswd,self.sqlDB,self.dbCharset,self.bkPath))
            
            print 'The database backup success! %s'  %time.strftime('%Y-%m-%d %H:%M:%S')
        #异常
        except MySQLdb.Error,err_msg:
            print "MySQL error msg:",err_msg



bk = MetBackup()
bk.backupDB()


