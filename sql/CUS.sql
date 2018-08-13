-- phpMyAdmin SQL Dump
-- version 4.8.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: 2018-08-13 13:47:32
-- 服务器版本： 8.0.11
-- PHP Version: 7.2.8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `itrsso_cus`
--

-- --------------------------------------------------------

--
-- 表的结构 `broadcast_task_queue`
--

CREATE TABLE `broadcast_task_queue` (
  `id` int(11) NOT NULL COMMENT 'UCS广播任务id',
  `task` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '命令',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='广播指令队列' ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- 表的结构 `sys`
--

CREATE TABLE `sys` (
  `id` int(11) NOT NULL,
  `variables` varchar(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `value` varchar(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `last_update_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `task_queue`
--

CREATE TABLE `task_queue` (
  `id` int(11) NOT NULL COMMENT 'UCS监听任务号',
  `task` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '命令',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- 表的结构 `temp_token`
--

CREATE TABLE `temp_token` (
  `id` int(11) NOT NULL COMMENT '序号',
  `token` text COLLATE utf8_unicode_ci NOT NULL COMMENT 'token',
  `username` varchar(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '用户名'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `user`
--

CREATE TABLE `user` (
  `uid` int(11) NOT NULL COMMENT '唯一用户标识码',
  `username` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '用户名',
  `password` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '密码',
  `nickname` varchar(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '昵称',
  `credit` int(11) NOT NULL COMMENT '积分',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后更新日期',
  `options` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '个性化配置,此处可存储不必要或子系统特别定义的用户配置项,json格式'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='用户表';

--
-- Indexes for dumped tables
--

--
-- Indexes for table `broadcast_task_queue`
--
ALTER TABLE `broadcast_task_queue`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sys`
--
ALTER TABLE `sys`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `task_queue`
--
ALTER TABLE `task_queue`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Indexes for table `temp_token`
--
ALTER TABLE `temp_token`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`uid`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `broadcast_task_queue`
--
ALTER TABLE `broadcast_task_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'UCS广播任务id';

--
-- 使用表AUTO_INCREMENT `sys`
--
ALTER TABLE `sys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `task_queue`
--
ALTER TABLE `task_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'UCS监听任务号';

--
-- 使用表AUTO_INCREMENT `temp_token`
--
ALTER TABLE `temp_token`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '序号';

--
-- 使用表AUTO_INCREMENT `user`
--
ALTER TABLE `user`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT COMMENT '唯一用户标识码';
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
