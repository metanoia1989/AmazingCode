# -*- coding: utf-8 -*-
import linecache
from datetime import datetime
import MySQLdb

import courseStats
from register_stats import registerStats
from speakStats import speakStats

now = datetime.now();
fileName = "INFO." + str(now.year) + str(now.month).zfill(2) + str(now.day - 1).zfill(2) + '.log';
# 设置存放日志文件的目录
basePath = "F:\\web\\logs\\home\\";
path=basePath+fileName

config = {
    'host': '127.0.0.1',
    'port': 3306,
    'user': 'root',
    'passwd': '',
    'db': 'db_name',
    'charset': 'utf8'
}


# 每个用户登陆次数统计
# 每个用户访问次数统计
#


def analysisLoginTime():
    login_key = "loginRecord";  # 登陆关键词
    user_key = "dailyUsers";  # 用户访问关键词
    login_log = [];  # 登陆日志
    visit_log = [];  # 访问日志
    course_log = [];  # 课程打开日志
    speak_log = [];  # 口语练习日志
    audio_log = [];  # 视听课堂日志
    pk_log = [];  # pk完成记录
    pk_speak_log = [];  # pk练习记录
    dubbing_open_log = [];  # 配音打开次数
    dubbing_speak_log = [];  # 配音练习次数
    register_log = [];  # 注册次数

    time_speak = [];

    lines = linecache.getlines(path);

    # 筛选日志事件存入数组
    for line in lines:
        if (login_key in line):
            login_log.append(line);
        elif (user_key in line):
            visit_log.append(line);
        elif ('courseUsers' in line):  # 课程打开日志
            course_log.append(line);
        elif ('scoreReturn' in line):  # 口语练习日志
            speak_log.append(line);
        elif ('audiovisualRecord' in line):  # 视听课堂日志
            audio_log.append(line);
        elif ('pkScoreRecord' in line):  # pk完成
            pk_log.append(line);
        elif ('pkScoreReturn' in line):  # pk单词练习
            pk_speak_log.append(line)
        elif ('dubbingRecord' in line):  # 影视配音打开记录
            dubbing_open_log.append(line)
        elif ('dubbingScoreReturn' in line):  # 影视配音打分记录
            dubbing_speak_log.append(line)
        elif ('registerRecord' in line):
            register_log.append(line)
        elif ('onlineTime' in line):
            time_speak.append(line)

    try:
        #注册统计
        registerStats(register_log,config)
    except :
        print("注册统计异常")

    try:
        #用户统计
        statsUser(audio_log, course_log, dubbing_open_log, dubbing_speak_log, login_log, pk_log, pk_speak_log, speak_log,
                  visit_log)
    except:
        print("用户统计异常")
        # 课程统计
    try:
        courseStats.Stats(course_log).statsCourse(config)
    except:
        print("课程统计异常")

    try:
        # 口语练习统计
        speakStats(speak_log,config)
    except:
        print("口语统计异常")

    try:
        # 在线时长统计
        statsSpeakTime(time_speak);
    except:
        print("在线时长统计异常")

    #ip访问记录
    try:
        ipStats(visit_log,config)
    except:
        traceback.print_exc()
        print("ip访问统计异常")


# IP 地址统计
def ipStats(logs,config):
    import time
    for log in logs:
        data=log.replace("\n","").replace(" ","").split("|")
        if len(data)<12:
            continue

        # 创建连接
        db = MySQLdb.connect(**config);
        # 使用cursor()方法获取操作游标
        cur = db.cursor()
        sql_insert = "INSERT INTO `stats_ip` (`address`, `user_id`, `orgId`, `access_time`, `create_time`) VALUES ('"+data[12]+"', '"+data[10]+"', '"+data[8]+"', '"+time.strftime("%Y-%m-%d %H:%M:%S",time.localtime(int(float(data[2]))))+"', '"+datetime.now().strftime('%Y-%m-%d')+"');"
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


#在线时长统计
def statsSpeakTime(time):
    data = dict();
    for line in time:
        info = line.replace(" ", "").replace('\n', '').split('|');

        userId = info[10]+"|"+info[8];

        if info[10] == '':
            userId = info[6]+"|"+info[8]

        if userId in data.keys():
            data[userId].append({"time": info[2], "pageUrl": info[-1]})
        else:
            data[userId] = [{"time": info[2], "pageUrl": info[-1]}]

        testData = dict();

        for uKey in data.keys():
            urlTime = dict();
            for page in data[uKey]:
                urls = page['pageUrl'].split('/')
                if (len(urls) > 4):
                    url = urls[3];
                else:
                    url = page['pageUrl'];
                if url in urlTime.keys():
                    urlTime[url].append(page["time"]);
                else:
                    urlTime[url] = [page["time"]]
            testData[uKey] = urlTime;

    lessonTime=statsLessonTime(testData,"lesson");
    japaneseTime=statsLessonTime(testData,"japanese");      #日语练习时长
    audioTime= statsLessonTime(testData,"audiovisual");     #视听课堂时长
    dubbingTime=statsLessonTime(testData,"dubbing");         #影视配音时长
    competitorTime=statsLessonTime(testData,"competitor");   #真人pk时长

    insertDuration(data.keys(),lessonTime,japaneseTime,audioTime,dubbingTime,competitorTime);


#时长统计 lesson 传入要统计的类型keyword:lesson japanese audiovisual dubbing competitor
def statsLessonTime(data,keyword):
    lesson_time=dict();
    for user in data.keys():
        if keyword in data[user].keys():
            times = data[user][keyword];
            sum_time=0;
            for i in range(0, len(times)-1):
                time = int(float(times[i + 1])) - int(float(times[i]));
                if time>600:
                    time=600;
                sum_time+=time;
            lesson_time[user]=sum_time;
    return lesson_time;

#插入数据库
def insertDuration(keys, lessonTime, japaneseTime, audioTime, dubbingTime, competitorTime):
    # 创建连接
    db = MySQLdb.connect(**config);
    s = "VALUES";
    for key in keys:
        user=key.split("|")
        s+="('"+user[0]+"',"+user[1]+","+str(lessonTime.get(key,0))+","+str(japaneseTime.get(key,0))+","+str(audioTime.get(key,0))+","+str(dubbingTime.get(key,0))+","+str(competitorTime.get(key,0))+",'"+datetime.now().strftime('%Y-%m-%d')+"'),";


    # 使用cursor()方法获取操作游标
    cur = db.cursor()

    

    sql_insert = "INSERT INTO `stats_online` (`userId`,`lesson_duration`, `japanese_duration`, `audiovisual_duration`, `dubbing_duration`, `competitor_duration`, `create_time`)"+s;

    print(sql_insert)
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


def statsUser(audio_log, course_log, dubbing_open_log, dubbing_speak_log, login_log, pk_log, pk_speak_log, speak_log,
              visit_log):
    login_stats = statsLogin(login_log);
    open_stats = statsOpen(visit_log);
    course_stats = statsCourse(course_log);
    speak_stats = statsSpeak(speak_log);

    addMySql(login_stats, open_stats, course_stats, speak_stats, statsAudio(audio_log), statsPk(pk_log),
             statsPkSpeak(pk_speak_log), statsPkSpeak(dubbing_open_log), statsPk(dubbing_speak_log));


# pk完成统计
def statsPk(logs):
    user = {};
    for log in logs:
        info = log.replace(" ", "").replace('\n', '').split('|');
        if info[-1] == '':
            info[-1] = 0
        userId = info[10];
        if userId == '':
            userId = info[6];

        if userId in user:
            user[userId][0] = user[userId][0] + 1
            user[userId][1] = float(user[userId][1]) + float(info[-1])
        else:
            user[userId] = [1, float(info[-1])];

    return user;


# pk练习统计
def statsPkSpeak(logs):
    user = {};
    for log in logs:
        info = log.replace(" ", "").replace('\n', '').split('|');

        userId = info[10];
        if userId == '':
            userId = info[6];

        if userId in user:
            user[userId] = user[userId] + 1
        else:
            user[userId] = 1;
    return user;


# 视听课堂统计
def statsAudio(logs):
    user = {};
    for log in logs:
        info = log.replace(" ", "").replace('\n', '').split('|');

        userId = info[10];
        if userId == '':
            userId = info[6];

        if userId in user:
            user[userId] = user[userId] + 1
        else:
            user[userId] = 1;
    return user;


# 口语练习统计
def statsSpeak(logs):
    user = {};
    for log in logs:
        info = log.replace(" ", "").replace('\n', '').split('|');
        userId = info[10];
        if userId == '':
            userId = info[6];

        if info[-1] == '':
            info[-1] = 0

        if userId in user:

            user[userId][0] = user[userId][0] + 1

            user[userId][1] = float(user[userId][1]) + float(info[-1])
        else:
            user[userId] = [1, float(info[-1])];
    return user;


# 课程打开统计
def statsCourse(logs):
    user = {};
    for log in logs:
        info = log.replace(" ", "").replace('\n', '').split('|');

        userId = info[10];
        if userId == '':
            userId = info[6];

        if userId in user:
            user[userId] = user[userId] + 1
        else:
            user[userId] = 1;
    return user;


# 统计首页打开次数
def statsOpen(logs):
    user = {};

    for log in logs:
        info = log.replace(" ", "").replace('\n', '').split('|');

        userId = info[10];
        if userId == '':
            userId = info[6];

        if userId in user:
            user[userId][0] = user[userId][0] + 1
        else:
            user[userId] = [1, info[8]];
    return user;


# 统计登陆次数
def statsLogin(logs):
    user = {};

    for log in logs:
        userId = log.replace(" ", "").replace('\n', '').split('|')[10];
        if userId in user:
            user[userId] = user[userId] + 1
        else:
            user[userId] = 1;

    return user;


# 添加到数据库中(登陆统计,网站打开统计)
def addMySql(login_stats, open_stats, course_stats, speak_stats, audio_stats, pk_stats, pk_speak_stats,
             dubbing_open_stats, dubbin_speak_stats):
    # 连接配置信息



    # 创建连接
    db = MySQLdb.connect(**config);

    s = "values";

    for key in open_stats.keys():
        login_time = login_stats.get(key, 0);
        opens = open_stats.get(key)
        speak = speak_stats.get(key, [0, 0])
        pk = pk_stats.get(key, [0, 0]);
        dubbing_speak = dubbin_speak_stats.get(key, [0, 0]);
        s = s + "('" + key + "','" + opens[1] + "'," + str(login_time) + "," + str(
            opens[0]) + ",'" + datetime.now().strftime('%Y-%m-%d') + "'," + str(course_stats.get(key, 0)) + "," + str(
            speak[0]) + "," + str(speak[1]) + "," + str(
            audio_stats.get(key, 0)) + "," + str(pk[0]) + "," + str(pk[1]) + "," + str(
            pk_speak_stats.get(key, 0)) + "," + str(dubbing_open_stats.get(key, 0)) + "," + str(
            dubbing_speak[0]) + "," + str(dubbing_speak[1]) + "),"

    # 使用cursor()方法获取操作游标
    cur = db.cursor()
    sql_insert = "insert into stats_user(userId,organization_id,login_time,open_time,create_time,course_time,speak_time,speak_score,audio_time,pk_complete_time,pk_score,pk_speak_time,dubbing_time,dubbing_speak_time,dubbing_score)" + s;

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


analysisLoginTime();
