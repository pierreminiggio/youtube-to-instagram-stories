# youtube-to-instagram-stories

Migration :
```sql
-- phpMyAdmin SQL Dump
-- version 4.7.0
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le :  lun. 28 déc. 2020 à 17:11
-- Version du serveur :  5.7.17
-- Version de PHP :  5.6.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------

--
-- Structure de la table `instagram_stories_channel_youtube_channel`
--

CREATE TABLE `instagram_stories_channel_youtube_channel` (
  `id` int(11) NOT NULL,
  `instagram_id` int(11) NOT NULL,
  `youtube_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `instagram_story_youtube_video`
--

CREATE TABLE `instagram_story_youtube_video` (
  `id` int(11) NOT NULL,
  `instagram_id` int(11) NOT NULL,
  `youtube_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `instagram_stories_channel_youtube_channel`
--
ALTER TABLE `instagram_stories_channel_youtube_channel`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `instagram_story_youtube_video`
--
ALTER TABLE `instagram_story_youtube_video`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `instagram_stories_channel_youtube_channel`
--
ALTER TABLE `instagram_stories_channel_youtube_channel`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `instagram_story_youtube_video`

ALTER TABLE `instagram_story_youtube_video`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Structure de la table `youtube_video_unpostable_on_instagram_stories`
--

CREATE TABLE `youtube_video_unpostable_on_instagram_stories` (
  `id` int(11) NOT NULL,
  `youtube_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `youtube_video_unpostable_on_instagram_stories`
--
ALTER TABLE `youtube_video_unpostable_on_instagram_stories`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `youtube_video_unpostable_on_instagram_stories`
--
ALTER TABLE `youtube_video_unpostable_on_instagram_stories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
ALTER TABLE `instagram_story_youtube_video`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

```
