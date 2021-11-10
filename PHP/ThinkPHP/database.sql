CREATE TABLE `ls_config` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`shop_id` INT(10) NULL DEFAULT '0' COMMENT '店铺id',
	`type` VARCHAR(24) NULL DEFAULT NULL COMMENT '类型' COLLATE 'utf8mb4_general_ci',
	`name` VARCHAR(32) NOT NULL COMMENT '名称' COLLATE 'utf8mb4_general_ci',
	`value` LONGTEXT NULL DEFAULT NULL COMMENT '值' COLLATE 'utf8_general_ci',
	`update_time` INT(10) NULL DEFAULT NULL COMMENT '更新时间',
	PRIMARY KEY (`id`) USING BTREE
)
COMMENT='配置表'
COLLATE='utf8_general_ci'
ENGINE=InnoDB;

INSERT INTO `ls_config` (`shop_id`, `type`, `name`, `value`, `update_time`) VALUES (0, 'oa', 'name', 'xxxxxx', 1627522623);
INSERT INTO `ls_config` (`shop_id`, `type`, `name`, `value`, `update_time`) VALUES (0, 'oa', 'original_id', 'xxxx', 1627522623);
INSERT INTO `ls_config` (`shop_id`, `type`, `name`, `value`, `update_time`) VALUES (0, 'oa', 'app_id', 'xxxxx', 1627522623);
INSERT INTO `ls_config` (`shop_id`, `type`, `name`, `value`, `update_time`) VALUES (0, 'oa', 'secret', 'xxxxx', 1627522623);
INSERT INTO `ls_config` (`shop_id`, `type`, `name`, `value`, `update_time`) VALUES (0, 'oa', 'token', 'xxxxx', 1627522623);
INSERT INTO `ls_config` (`shop_id`, `type`, `name`, `value`, `update_time`) VALUES (0, 'oa', 'encoding_ses_key', 'xxxx', 1627522623);
INSERT INTO `ls_config` (`shop_id`, `type`, `name`, `value`, `update_time`) VALUES (0, 'oa', 'encryption_type', '1', 1627522623);
INSERT INTO `ls_config` (`shop_id`, `type`, `name`, `value`, `update_time`) VALUES (0, 'oa', 'qr_code', '', 1627522623);
INSERT INTO `ls_config` (`shop_id`, `type`, `name`, `value`, `update_time`) VALUES (0, 'storage', 'default', 'qcloud', 1630034308);
INSERT INTO `ls_config` (`shop_id`, `type`, `name`, `value`, `update_time`) VALUES (0, 'op', 'app_id', 'xxxx', 1627703631);
INSERT INTO `ls_config` (`shop_id`, `type`, `name`, `value`, `update_time`) VALUES (0, 'op', 'secret', 'xxxx', 1627703631);
INSERT INTO `ls_config` (`shop_id`, `type`, `name`, `value`, `update_time`) VALUES (0, 'mnp', 'name', 'xxx', NULL);
INSERT INTO `ls_config` (`shop_id`, `type`, `name`, `value`, `update_time`) VALUES (0, 'mnp', 'original_id', 'xxx', NULL);
INSERT INTO `ls_config` (`shop_id`, `type`, `name`, `value`, `update_time`) VALUES (0, 'mnp', 'qr_code', '', NULL);
INSERT INTO `ls_config` (`shop_id`, `type`, `name`, `value`, `update_time`) VALUES (0, 'mnp', 'app_id', 'xxx', NULL);
INSERT INTO `ls_config` (`shop_id`, `type`, `name`, `value`, `update_time`) VALUES (0, 'mnp', 'secret', 'xxx', NULL);
INSERT INTO `ls_config` (`shop_id`, `type`, `name`, `value`, `update_time`) VALUES (0, 'mnp', 'token', 'LikeMall', NULL);
INSERT INTO `ls_config` (`shop_id`, `type`, `name`, `value`, `update_time`) VALUES (0, 'mnp', 'encoding_ses_key', '', NULL);
INSERT INTO `ls_config` (`shop_id`, `type`, `name`, `value`, `update_time`) VALUES (0, 'mnp', 'encryption_type', '1', NULL);
INSERT INTO `ls_config` (`shop_id`, `type`, `name`, `value`, `update_time`) VALUES (0, 'mnp', 'data_type', '1', NULL);
INSERT INTO `ls_config` (`shop_id`, `type`, `name`, `value`, `update_time`) VALUES (0, 'share', 'h5', '{"h5_share_title":"xxxx","h5_share_intro":"xxxx","h5_share_image":"uploads\\/images\\/20210729143244daed12484.jpg"}', 1627787561);
INSERT INTO `ls_config` (`shop_id`, `type`, `name`, `value`, `update_time`) VALUES (0, 'share', 'mnp', '{"mnp_share_title":"xxxx"}', 1627787561);
