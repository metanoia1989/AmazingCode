# -*- coding: UTF-8 -*-
 
import paramiko
import os
 
#抓取MM
class SFTP:
 
    #页面初始化
    def __init__(self):
        self.ftp = 'ip_address'
        self.ftpUser = 'root'
        self.ftpPwd = 'password'
        self.mirrorPath = '/alidata/www/met/Uploads/'


    def sftp_download(self,host,port,username,password,local,remote):
        sf = paramiko.Transport((host,port))
        sf.connect(username = username,password = password)
        sftp = paramiko.SFTPClient.from_transport(sf)
        try:
            if os.path.isdir(local):#判断本地参数是目录还是文件
                print 'local file:'+local
                for f in sftp.listdir(remote):#遍历远程目录
                    print "download file:"+remote+f
                    sftp.get(os.path.join(remote+f),os.path.join(local+f))#下载目录中文件
            else:
                sftp.get(remote,local)#下载文件
        except Exception,e:
            print('download exception:',e)
        sf.close()


sftp = SFTP()
folders = ['course']
sftp.sftp_download(sftp.ftp,22,sftp.ftpUser,sftp.ftpPwd,sftp.mirrorPath+'course/',sftp.mirrorPath+'course/')


