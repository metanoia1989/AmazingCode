# -*- coding: utf-8 -*-
# 注册日志统计
from datetime import datetime

import MySQLdb
# 解析注册日志并提交数据库
def registerStats(logs,config):
    course = dict();
    for log in logs:
        info = log.replace(" ", "").replace('\n', '').split('|');

        orgId = info[8];

        if orgId in course.keys():
            course[orgId]=course[orgId]+1;
        else:
            course[orgId] = 1;

    insetSql(course,config)


def insetSql(register,config):

    # 创建连接
    db = MySQLdb.connect(**config);
    # 使用cursor()方法获取操作游标
    cur = db.cursor()
    sql_insert = "INSERT INTO `stats_register` (`org_id`,`register_time`,`create_time`) VALUES";
    for ckey in register.keys():
        sql_insert += "(" + ckey + "," + str(register.get(ckey,0)) + ",'"+ datetime.now().strftime('%Y-%m-%d')+"'),";

    try:
        cur.execute(sql_insert[0:-1])
        # 提交
        db.commit()
    except Exception as e:
        # 错误回滚
        print(e)
        db.rollback()
    finally:
        db.close()
