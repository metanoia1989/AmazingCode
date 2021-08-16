# -*- coding: utf-8 -*-
# 课程使用情况统计

from datetime import datetime

import MySQLdb



class Stats(object):
    # 传入课程使用日志
    def __init__(self, course_log):
        self.log = course_log;
    # 解析课程使用日志
    def statsCourse(self,config):
        course = dict();
        for log in self.log:
            info = log.replace(" ", "").replace('\n', '').split('|');

            orgId = info[8];

            if orgId in course.keys():
                course.get(orgId).append([info[10], info[12]]);
            else:
                course[orgId] = [[info[10], info[12]]];

        for key in course.keys():

            c_time = dict();
            c_user = dict();
            data = course.get(key)
            for d in data:
                if d[1] in c_time.keys():
                    c_time[d[1]] += 1;
                else:
                    c_time[d[1]] = 1;

                if d[1] in c_user.keys():
                    if not d[0] in c_user[d[1]]:
                        c_user[d[1]].append(d[0]);
                else:
                    c_user[d[1]] = [d[0]]

            insetSql(key,c_user,c_time,config);

def insetSql(key, c_user, c_time,config):

        # 创建连接
        db = MySQLdb.connect(**config);
        # 使用cursor()方法获取操作游标
        cur = db.cursor()
        sql_insert = "INSERT INTO `stats_course` (`course_id`, `organization_id`, `open_time`, `user_count`, `create_time`) VALUES";
        for ckey in c_time.keys():
             sql_insert+="("+ckey+","+str(key)+","+str(c_time.get(ckey,0))+","+str(len(c_user.get(ckey)))+", '"+datetime.now().strftime('%Y-%m-%d')+"'),";

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

# 课程实体类,用于存储课程id,使用时间,机构id
class Course(object):
    def __init__(self, course_id, org_id, time):
        self.course_id = course_id;
        self.org_id = org_id;
        self.time = time;

    def getCourseId(self):
        return self.course_id;

    def getOrgId(self):
        return self.org_id;

    def getTime(self):
        return self.time;
