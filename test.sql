CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `username` varchar(30) NOT NULL,
  `password` varchar(128) NOT NULL,
  `name` varchar(255) NOT NULL,
  `avatar` varchar(255) NOT NULL DEFAULT '0.png',
  `level` text NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `user` (`id`, `username`, `password`, `name`, `avatar`, `level`, `active`) VALUES
(1, 'test', 'c21bd8e939c9907ca6a2748d19e467b5040c0a5366a3af65e0dae1807a3d45c033f2152a164e7fe707c405a935aef080286b50006ca78a06c8c8d07cb311a34b', 'Test', '0.png', ';dev;', 1);


ALTER TABLE `user`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
