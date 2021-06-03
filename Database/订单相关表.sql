CREATE TABLE `kqc_coupon` (
	`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	`sid` INT(11) NOT NULL COMMENT '商家id，0为平台优惠券',
	`mid` INT(11) NOT NULL DEFAULT '0' COMMENT '套餐id，0为店铺优惠券',
	`full_price` CHAR(11) NULL DEFAULT NULL COMMENT '满多少' COLLATE 'utf8_general_ci',
	`cut_price` CHAR(11) NULL DEFAULT NULL COMMENT '减多少' COLLATE 'utf8_general_ci',
	`start_time` DATETIME NULL DEFAULT NULL COMMENT '优惠券开始时间',
	`end_time` DATETIME NULL DEFAULT NULL COMMENT '优惠券结束时间',
	`surplus` INT(11) UNSIGNED NULL DEFAULT '1' COMMENT '库存',
	`is_del` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '是否已被删除',
	PRIMARY KEY (`id`) USING BTREE
)
COMMENT='优惠券表'
COLLATE='utf8_general_ci'
ENGINE=MyISAM;


CREATE TABLE `kqc_coupon_user` (
	`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	`cou_id` INT(11) NOT NULL COMMENT '优惠券id',
	`sid` INT(11) NOT NULL COMMENT '商家ID 当为平台优惠券时为0',
	`mid` VARCHAR(255) NOT NULL DEFAULT '0' COMMENT '套餐ID 当为店铺优惠券时为0' COLLATE 'utf8_general_ci',
	`start_time` DATETIME NULL DEFAULT NULL COMMENT '优惠券开始时间',
	`end_time` DATETIME NULL DEFAULT NULL COMMENT '优惠券结束时间',
	`full_price` CHAR(11) NULL DEFAULT NULL COMMENT '满多少' COLLATE 'utf8_general_ci',
	`cut_price` CHAR(11) NULL DEFAULT NULL COMMENT '减多少' COLLATE 'utf8_general_ci',
	`uid` INT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '用户ID',
	`use` TINYINT(1) NOT NULL COMMENT '订单id, 0为未使用',
	PRIMARY KEY (`id`) USING BTREE
)
COMMENT='用户优惠券表，（与优惠券表分离的原因是：若商家或平台将优惠券删除或修改，已领券的用户仍可看到该优惠券）'
COLLATE='utf8_general_ci'
ENGINE=MyISAM;


CREATE TABLE `a_order` (
	`id` INT(10) NOT NULL AUTO_INCREMENT COMMENT '订单id',
	`uid` INT(10) NOT NULL COMMENT '用户id',
	`folder_id` INT(10) NOT NULL COMMENT '文件夹id',
	`order_sn` VARCHAR(255) NOT NULL COMMENT '订单号' COLLATE 'utf8_general_ci',
	`vip` VARCHAR(20) NULL DEFAULT NULL COMMENT '购买的vip类型, free 免费, month 月费  year 年付 foeve 永久' COLLATE 'utf8_general_ci',
	`trade_no` VARCHAR(255) NULL DEFAULT NULL COMMENT '微信订单号' COLLATE 'utf8_general_ci',
	`price` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT '订单金额',
	`coupon_id` INT(11) NOT NULL DEFAULT '0' COMMENT '用户优惠券id',
	`coupon_cut` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT '优惠券减去的金额',
	`pay_price` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT '支付金额',
	`status` TINYINT(2) NOT NULL DEFAULT '0' COMMENT '订单状态（0未支付，1已支付）',
	`add_time` INT(10) NULL DEFAULT NULL COMMENT '订单创建时间',
	`pay_time` INT(10) NOT NULL DEFAULT '0' COMMENT '支付时间',
	PRIMARY KEY (`id`),
	INDEX `uid` (`uid`),
	INDEX `folder_id` (`folder_id`) 
)
COMMENT='订单表'
COLLATE='utf8_general_ci'
ENGINE=InnoDB;


CREATE TABLE `kqc_menu_order` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`order_id` CHAR(20) NOT NULL COMMENT '订单号' COLLATE 'utf8_general_ci',
	`price` DECIMAL(10,2) NOT NULL COMMENT '订单金额',
	`deposit` DECIMAL(10,2) NOT NULL COMMENT '订金',
	`sid` INT(11) NOT NULL COMMENT '商家id',
	`mid` INT(11) NOT NULL COMMENT '套餐id',
	`uid` INT(11) NOT NULL COMMENT '用户id',
	`status` TINYINT(2) NOT NULL COMMENT '订单状态: 0(未付款),1(已付订金),2(已付全款),3(待支付定金)',
	`offline` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '是否为线下付款',
	`pay` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT '已付金额',
	`name` VARCHAR(50) NOT NULL COMMENT '联系人' COLLATE 'utf8_general_ci',
	`phone` CHAR(11) NOT NULL COMMENT '联系电话' COLLATE 'utf8_general_ci',
	`coupon_uid` INT(11) NOT NULL DEFAULT '0' COMMENT '用户优惠券id',
	`coupon_cut` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT '优惠券减去的金额',
	`user_remark` VARCHAR(255) NULL DEFAULT NULL COMMENT '客户留言' COLLATE 'utf8_general_ci',
	`studio_remark` VARCHAR(255) NULL DEFAULT NULL COMMENT '商家备注' COLLATE 'utf8_general_ci',
	`shootday` VARCHAR(255) NULL DEFAULT NULL COMMENT '预约拍摄时间' COLLATE 'utf8_general_ci',
	`shootaddress` VARCHAR(255) NULL DEFAULT NULL COMMENT '预约拍摄地点' COLLATE 'utf8_general_ci',
	`add_time` INT(11) NOT NULL COMMENT '下单时间',
	`pay_time1` INT(11) NULL DEFAULT NULL COMMENT '首付时间',
	`pay_time2` INT(11) NULL DEFAULT NULL COMMENT '付余额时间',
	`modified_time` INT(11) NULL DEFAULT NULL COMMENT '修改时间',
	`virtual` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '是否虚拟订单',
	`hide` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '是否隐藏',
	`is_fast` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '是否为快速制单',
	`error` VARCHAR(255) NULL DEFAULT NULL COMMENT '系统备注' COLLATE 'utf8_general_ci',
	`marry_date` VARCHAR(255) NULL DEFAULT NULL COMMENT '婚期' COLLATE 'utf8_general_ci',
	`commemorate` VARCHAR(255) NULL DEFAULT NULL COMMENT '结婚纪念日' COLLATE 'utf8_general_ci',
	`spouse` VARCHAR(255) NULL DEFAULT NULL COMMENT '配偶姓名' COLLATE 'utf8_general_ci',
	`spouse_phone` CHAR(11) NULL DEFAULT NULL COMMENT '配偶联系电话' COLLATE 'utf8_general_ci',
	`type` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '订单类型: 0:线上订单, 1:门店订单, 2:自定义订单(需客户确认) 3:草稿',
	`pay_type1` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '0线上, 1线下',
	`pay_type2` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '0线上, 1线下',
	`edit_time` INT(11) NULL DEFAULT NULL COMMENT '最后修改时间',
	`employee_id` INT(11) NOT NULL DEFAULT '0' COMMENT '开单员工',
	`general` TEXT(65535) NULL DEFAULT NULL COMMENT '通用字段' COLLATE 'utf8_general_ci',
	`album_uid` INT(11) NOT NULL DEFAULT '0' COMMENT '云相册用户id',
	PRIMARY KEY (`id`) USING BTREE
)
COMMENT='订单表'
COLLATE='utf8_general_ci'
ENGINE=MyISAM;


CREATE TABLE `kqc_studio_setmenu` (
	`s_menu_id` INT(11) NOT NULL AUTO_INCREMENT,
	`s_menu_name` TEXT(65535) NOT NULL COMMENT '套餐标题' COLLATE 'utf8_general_ci',
	`s_menu_intr` TEXT(65535) NULL DEFAULT NULL COMMENT '套餐介绍' COLLATE 'utf8_general_ci',
	`s_menu_salenum` INT(11) NOT NULL DEFAULT '0' COMMENT '套餐销量',
	`s_menu_truingnum` VARCHAR(5) NULL DEFAULT NULL COMMENT '套餐精修图片数' COLLATE 'utf8_general_ci',
	`s_menu_deposit` DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '订金',
	`s_menu_price` DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '套餐价格',
	`s_menu_original_price` VARCHAR(10) NULL DEFAULT NULL COMMENT '套餐原价' COLLATE 'utf8_general_ci',
	`s_menu_sid` INT(11) NOT NULL COMMENT '套餐所属影楼',
	`s_menu_sort` INT(10) NULL DEFAULT NULL COMMENT '新版后台套餐排序',
	`s_menu_category` VARCHAR(255) NOT NULL COMMENT '套餐类型' COLLATE 'utf8_general_ci',
	`s_menu_service` INT(11) NOT NULL DEFAULT '0' COMMENT '套餐服务方式1包门票2包住宿3全包',
	`s_menu_preview` VARCHAR(255) NULL DEFAULT NULL COMMENT '套餐小程序预览码' COLLATE 'utf8_general_ci',
	`s_menu_cover` VARCHAR(255) NULL DEFAULT NULL COMMENT '套餐封面' COLLATE 'utf8_general_ci',
	`s_menu_popularnum` INT(11) NULL DEFAULT '0' COMMENT '套餐人气',
	`s_menu_warming` TEXT(65535) NULL DEFAULT NULL COMMENT '套餐详细图片' COLLATE 'utf8_general_ci',
	`s_menu_product_descrip` TEXT(65535) NULL DEFAULT NULL COMMENT '产品说明' COLLATE 'utf8_general_ci',
	`s_menu_trades` VARCHAR(30) NULL DEFAULT NULL COMMENT '套餐所含商品' COLLATE 'utf8_general_ci',
	`s_menu_clothes_num_man` INT(10) NOT NULL DEFAULT '0' COMMENT '新郎服装数/服装套数',
	`s_menu_clothes_num` INT(10) NULL DEFAULT '0' COMMENT '新娘服装数',
	`s_menu_clothes_intr` VARCHAR(255) NULL DEFAULT NULL COMMENT '服装说明' COLLATE 'utf8_general_ci',
	`s_menu_model` INT(10) NULL DEFAULT NULL COMMENT '造型数',
	`s_menu_model_intr` VARCHAR(255) NULL DEFAULT NULL COMMENT '造型说明' COLLATE 'utf8_general_ci',
	`s_menu_photo_album` VARCHAR(6) NULL DEFAULT '' COMMENT '相册数量|相框数量' COLLATE 'utf8_general_ci',
	`s_menu_album_intr` TEXT(65535) NULL DEFAULT NULL COMMENT '相册说明' COLLATE 'utf8_general_ci',
	`s_menu_frame_intr` TEXT(65535) NULL DEFAULT NULL COMMENT '相框说明' COLLATE 'utf8_general_ci',
	`s_menu_view` VARCHAR(20) NULL DEFAULT NULL COMMENT '套餐包含的景点' COLLATE 'utf8_general_ci',
	`s_menu_str` VARCHAR(255) NULL DEFAULT NULL COMMENT '景点字符串' COLLATE 'utf8_general_ci',
	`s_menu_dresser` VARCHAR(10) NULL DEFAULT NULL COMMENT '套餐化妆师' COLLATE 'utf8_general_ci',
	`s_menu_camerist` VARCHAR(30) NULL DEFAULT NULL COMMENT '套餐的摄影师' COLLATE 'utf16_general_ci',
	`s_menu_order` INT(11) NULL DEFAULT '0' COMMENT '套餐排序',
	`s_menu_photonum` VARCHAR(5) NULL DEFAULT '0' COMMENT '套餐底片数' COLLATE 'utf8_general_ci',
	`s_menu_bgimg` VARCHAR(255) NULL DEFAULT NULL COMMENT '套餐内的背景图' COLLATE 'utf8_general_ci',
	`s_menu_show` INT(11) NOT NULL DEFAULT '1' COMMENT '上下架  0.不显示 1.显示',
	`s_menu_shoot_address` VARCHAR(100) NULL DEFAULT NULL COMMENT '拍摄地点' COLLATE 'utf8_general_ci',
	`s_menu_out_time` VARCHAR(20) NULL DEFAULT NULL COMMENT '出行时间' COLLATE 'utf8_general_ci',
	`s_menu_use_time` VARCHAR(20) NULL DEFAULT NULL COMMENT '拍摄时长' COLLATE 'utf8_general_ci',
	`s_menu_add_shootpay` VARCHAR(255) NULL DEFAULT NULL COMMENT '加拍费' COLLATE 'utf8_general_ci',
	`s_menu_self_pay` VARCHAR(255) NULL DEFAULT NULL COMMENT '自费拍摄项目及费用' COLLATE 'utf8_general_ci',
	`s_menu_hotel_pay` VARCHAR(100) NULL DEFAULT NULL COMMENT '酒店住宿及费用' COLLATE 'utf8_general_ci',
	`s_menu_add_livepay` VARCHAR(100) NULL DEFAULT '0|' COMMENT '加住费用' COLLATE 'utf8_general_ci',
	`s_menu_food_pay` VARCHAR(100) NULL DEFAULT NULL COMMENT '用餐费用' COLLATE 'utf8_general_ci',
	`s_menu_give_service` VARCHAR(100) NULL DEFAULT NULL COMMENT '赠送服务' COLLATE 'utf8_general_ci',
	`s_menu_play_service` VARCHAR(100) NULL DEFAULT NULL COMMENT '游玩服务' COLLATE 'utf8_general_ci',
	`s_menu_index_order` INT(11) NULL DEFAULT '0' COMMENT '首页推荐套餐排序',
	`s_menu_showin_studio` TINYINT(1) NULL DEFAULT '0' COMMENT '禁止在影楼总套餐页面显示',
	`s_menu_pc_cate` INT(11) NULL DEFAULT NULL COMMENT '所属pc首页套餐类型',
	`s_menu_pc_iorder` INT(11) NULL DEFAULT NULL COMMENT '首页分类套餐排序',
	`s_menu_coupon` TINYINT(1) NULL DEFAULT '0' COMMENT '是否可用优惠券0 1',
	`s_menu_fxindex` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '首页推荐套餐',
	`s_menu_fxstate` TINYINT(4) NOT NULL DEFAULT '0' COMMENT '0不推广   1推广',
	`s_menu_order_git` VARCHAR(50) NULL DEFAULT NULL COMMENT '订单礼' COLLATE 'utf8_general_ci',
	`s_menu_allpay_git` VARCHAR(50) NULL DEFAULT '0|' COMMENT '全款礼' COLLATE 'utf8_general_ci',
	`s_menu_scene_info` VARCHAR(5) NULL DEFAULT '2|1|1' COMMENT '拍摄场景|内景数量|外景数量' COLLATE 'utf8_general_ci',
	`s_menu_scene_inside` TEXT(65535) NULL DEFAULT NULL COMMENT '内景说明' COLLATE 'utf8_general_ci',
	`s_menu_scene_outside` TEXT(65535) NULL DEFAULT NULL COMMENT '外景说明' COLLATE 'utf8_general_ci',
	`s_menu_address` INT(11) NULL DEFAULT NULL COMMENT '拍摄目的地',
	`s_menu_activ_id` INT(6) UNSIGNED ZEROFILL NULL DEFAULT NULL COMMENT '活动ID,1:双11活动',
	`s_menu_click` INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '套餐点击量',
	`s_menu_audit` TINYINT(1) NOT NULL COMMENT '审核情况，0：未审核；1：通过；2：未通过；3：草稿； 4：快速制单',
	`s_menu_audit_mess` VARCHAR(255) NULL DEFAULT NULL COMMENT '审核不通过原因' COLLATE 'utf8_general_ci',
	`s_menu_edit_time` INT(11) NOT NULL COMMENT '修改时间',
	`s_menu_bright` VARCHAR(50) NULL DEFAULT NULL COMMENT '套餐亮点' COLLATE 'utf8_general_ci',
	`s_menu_style` VARCHAR(50) NULL DEFAULT NULL COMMENT '套餐风格' COLLATE 'utf8_general_ci',
	`s_menu_scene` VARCHAR(50) NULL DEFAULT NULL COMMENT '套餐场景' COLLATE 'utf8_general_ci',
	`s_menu_know` TEXT(65535) NULL DEFAULT NULL COMMENT '购买须知' COLLATE 'utf8_general_ci',
	`is_del` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '是否已删除 0:否 1:是',
	`is_fast` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '是否为快速制单，is_del为1不显示在套餐列表',
	`s_menu_warm_tip` TEXT(65535) NULL DEFAULT NULL COMMENT '温馨提示' COLLATE 'utf8_general_ci',
	`content` TEXT(65535) NULL DEFAULT NULL COMMENT '通用订单内容' COLLATE 'utf8_general_ci',
	`copy` INT(11) NOT NULL DEFAULT '0' COMMENT '复制来源',
	PRIMARY KEY (`s_menu_id`) USING BTREE,
	INDEX `s_menu_sid` (`s_menu_sid`) USING BTREE
)
COMMENT='套餐表'
COLLATE='utf8_general_ci'
ENGINE=InnoDB
;