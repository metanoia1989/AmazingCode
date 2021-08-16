# -*- coding: utf-8 -*-
# 口语训练统计
from collections import Counter
from datetime import datetime

import MySQLdb


# 解析口语训练日志
def speakStats(logs,config):
    course = dict();
    for log in logs:
        info = log.replace(" ", "").replace('\n', '').split('|');

        orgId = info[8];

        if orgId in course.keys():
            course.get(orgId).append([info[10], info[14], info[16]]);  # user lesson  mode
        else:
            course[orgId] = [[info[10], info[14], info[16]]];

    for key in course.keys():

        c_time = dict();
        c_user = dict();
        c_mode = dict();
        data = course.get(key)
        for d in data:
            if d[1] in c_time.keys():  # 课程次数
                c_time[d[1]] += 1;
            else:
                c_time[d[1]] = 1;

            if d[1] in c_user.keys():  # 学习人数
                if not d[0] in c_user[d[1]]:
                    c_user[d[1]].append(d[0]);
            else:
                c_user[d[1]] = [d[0]]

            if d[1] in c_mode.keys():  # 练习模式
                c_mode[d[1]].append(d[2]);
            else:
                c_mode[d[1]] = [d[2]]

        insetSql(key, c_user, c_time, c_mode,config);


def insetSql(key, c_user, c_time,c_mode,config):

    # 创建连接
    db = MySQLdb.connect(**config);
    # 使用cursor()方法获取操作游标
    cur = db.cursor()
    sql_insert = "INSERT INTO `stats_lesson` (`lesson_id`, `organization_id`, `open_time`, `user_time`, `create_time`,`mode_2`, `mode_3`, `mode_4`, `mode_5`, `mode_6`) VALUES";
    for ckey in c_time.keys():

        mode= c_mode.get(ckey,[])
        s_mode=Counter(mode);

        sql_insert += "(" + ckey + "," + str(key) + "," + str(c_time.get(ckey, 0)) + "," + str(
            len(c_user.get(ckey))) + ", '" + datetime.now().strftime('%Y-%m-%d') + "',"+str(s_mode.get('2',0))+","+str(s_mode.get('3',0))+","+str(s_mode.get('4',0))+","+str(s_mode.get('5',0))+","+str(s_mode.get('6',0))+"),";


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
