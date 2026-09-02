-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 02, 2026 at 04:16 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_kare`
--

-- --------------------------------------------------------

--
-- Table structure for table `districts`
--

CREATE TABLE `districts` (
  `id` int(11) NOT NULL,
  `state_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `districts`
--

INSERT INTO `districts` (`id`, `state_id`, `name`) VALUES
(1, 1, 'South Andaman'),
(2, 1, 'North and Middle Andaman'),
(3, 1, 'Nicobar'),
(4, 2, 'Anantapur'),
(5, 2, 'Chittoor'),
(6, 2, 'East Godavari'),
(7, 2, 'Guntur'),
(8, 2, 'Krishna'),
(9, 2, 'Kurnool'),
(10, 2, 'Nellore'),
(11, 2, 'Prakasam'),
(12, 2, 'Srikakulam'),
(13, 2, 'Visakhapatnam'),
(14, 2, 'Vizianagaram'),
(15, 2, 'West Godavari'),
(16, 2, 'Kadapa'),
(17, 2, 'Tirupati'),
(18, 2, 'Kakinada'),
(19, 3, 'Tawang'),
(20, 3, 'West Kameng'),
(21, 3, 'East Kameng'),
(22, 3, 'Papum Pare'),
(23, 3, 'Lower Subansiri'),
(24, 3, 'Upper Subansiri'),
(25, 3, 'West Siang'),
(26, 3, 'East Siang'),
(27, 3, 'Lohit'),
(28, 3, 'Changlang'),
(29, 3, 'Tirap'),
(30, 3, 'Lower Dibang Valley'),
(31, 3, 'Upper Dibang Valley'),
(32, 3, 'Kurung Kumey'),
(33, 3, 'Namsai'),
(34, 4, 'Kamrup'),
(35, 4, 'Kamrup Metropolitan'),
(36, 4, 'Dibrugarh'),
(37, 4, 'Jorhat'),
(38, 4, 'Nagaon'),
(39, 4, 'Sivasagar'),
(40, 4, 'Tinsukia'),
(41, 4, 'Barpeta'),
(42, 4, 'Cachar'),
(43, 4, 'Golaghat'),
(44, 4, 'Darrang'),
(45, 4, 'Dhemaji'),
(46, 4, 'Dhubri'),
(47, 4, 'Goalpara'),
(48, 4, 'Karimganj'),
(49, 4, 'Lakhimpur'),
(50, 4, 'Nalbari'),
(51, 4, 'Sonitpur'),
(52, 5, 'Patna'),
(53, 5, 'Gaya'),
(54, 5, 'Bhagalpur'),
(55, 5, 'Muzaffarpur'),
(56, 5, 'Darbhanga'),
(57, 5, 'Purnia'),
(58, 5, 'Nalanda'),
(59, 5, 'Rohtas'),
(60, 5, 'Saran'),
(61, 5, 'Vaishali'),
(62, 5, 'Begusarai'),
(63, 5, 'Samastipur'),
(64, 5, 'Munger'),
(65, 5, 'West Champaran'),
(66, 5, 'East Champaran'),
(67, 5, 'Nawada'),
(68, 5, 'Katihar'),
(69, 6, 'Chandigarh'),
(70, 7, 'Raipur'),
(71, 7, 'Bilaspur'),
(72, 7, 'Durg'),
(73, 7, 'Korba'),
(74, 7, 'Raigarh'),
(75, 7, 'Rajnandgaon'),
(76, 7, 'Bastar'),
(77, 7, 'Dhamtari'),
(78, 7, 'Janjgir-Champa'),
(79, 7, 'Kabirdham'),
(80, 7, 'Kanker'),
(81, 7, 'Koriya'),
(82, 7, 'Mahasamund'),
(83, 7, 'Surguja'),
(84, 7, 'Jashpur'),
(85, 8, 'Dadra and Nagar Haveli'),
(86, 8, 'Daman'),
(87, 8, 'Diu'),
(88, 9, 'New Delhi'),
(89, 9, 'North Delhi'),
(90, 9, 'South Delhi'),
(91, 9, 'East Delhi'),
(92, 9, 'West Delhi'),
(93, 9, 'Central Delhi'),
(94, 9, 'North East Delhi'),
(95, 9, 'North West Delhi'),
(96, 9, 'South East Delhi'),
(97, 9, 'South West Delhi'),
(98, 9, 'Shahdara'),
(99, 10, 'North Goa'),
(100, 10, 'South Goa'),
(101, 11, 'Ahmedabad'),
(102, 11, 'Surat'),
(103, 11, 'Vadodara'),
(104, 11, 'Rajkot'),
(105, 11, 'Bhavnagar'),
(106, 11, 'Jamnagar'),
(107, 11, 'Junagadh'),
(108, 11, 'Gandhinagar'),
(109, 11, 'Anand'),
(110, 11, 'Bharuch'),
(111, 11, 'Kutch'),
(112, 11, 'Mehsana'),
(113, 11, 'Navsari'),
(114, 11, 'Patan'),
(115, 11, 'Porbandar'),
(116, 11, 'Valsad'),
(117, 12, 'Faridabad'),
(118, 12, 'Gurugram'),
(119, 12, 'Panipat'),
(120, 12, 'Ambala'),
(121, 12, 'Karnal'),
(122, 12, 'Hisar'),
(123, 12, 'Rohtak'),
(124, 12, 'Sonipat'),
(125, 12, 'Yamunanagar'),
(126, 12, 'Panchkula'),
(127, 12, 'Sirsa'),
(128, 12, 'Bhiwani'),
(129, 12, 'Jind'),
(130, 12, 'Kaithal'),
(131, 12, 'Kurukshetra'),
(132, 13, 'Shimla'),
(133, 13, 'Kangra'),
(134, 13, 'Mandi'),
(135, 13, 'Solan'),
(136, 13, 'Una'),
(137, 13, 'Bilaspur'),
(138, 13, 'Chamba'),
(139, 13, 'Hamirpur'),
(140, 13, 'Kinnaur'),
(141, 13, 'Kullu'),
(142, 13, 'Lahaul and Spiti'),
(143, 13, 'Sirmaur'),
(144, 14, 'Srinagar'),
(145, 14, 'Jammu'),
(146, 14, 'Anantnag'),
(147, 14, 'Baramulla'),
(148, 14, 'Budgam'),
(149, 14, 'Pulwama'),
(150, 14, 'Kupwara'),
(151, 14, 'Kathua'),
(152, 14, 'Udhampur'),
(153, 14, 'Rajouri'),
(154, 15, 'Ranchi'),
(155, 15, 'Dhanbad'),
(156, 15, 'Jamshedpur (East Singhbhum)'),
(157, 15, 'Bokaro'),
(158, 15, 'Deoghar'),
(159, 15, 'Hazaribagh'),
(160, 15, 'Giridih'),
(161, 15, 'Palamu'),
(162, 15, 'Ramgarh'),
(163, 15, 'Dumka'),
(164, 15, 'Chatra'),
(165, 15, 'Garhwa'),
(166, 16, 'Bengaluru Urban'),
(167, 16, 'Bengaluru Rural'),
(168, 16, 'Mysuru'),
(169, 16, 'Belagavi'),
(170, 16, 'Hubballi-Dharwad'),
(171, 16, 'Mangaluru (Dakshina Kannada)'),
(172, 16, 'Kalaburagi'),
(173, 16, 'Ballari'),
(174, 16, 'Shivamogga'),
(175, 16, 'Tumakuru'),
(176, 16, 'Davangere'),
(177, 16, 'Bidar'),
(178, 16, 'Bijapur (Vijayapura)'),
(179, 16, 'Udupi'),
(180, 16, 'Chikkamagaluru'),
(181, 16, 'Raichur'),
(182, 17, 'Thiruvananthapuram'),
(183, 17, 'Kollam'),
(184, 17, 'Pathanamthitta'),
(185, 17, 'Alappuzha'),
(186, 17, 'Kottayam'),
(187, 17, 'Idukki'),
(188, 17, 'Ernakulam'),
(189, 17, 'Thrissur'),
(190, 17, 'Palakkad'),
(191, 17, 'Malappuram'),
(192, 17, 'Kozhikode'),
(193, 17, 'Wayanad'),
(194, 17, 'Kannur'),
(195, 17, 'Kasaragod'),
(196, 18, 'Leh'),
(197, 18, 'Kargil'),
(198, 19, 'Lakshadweep'),
(199, 20, 'Bhopal'),
(200, 20, 'Indore'),
(201, 20, 'Gwalior'),
(202, 20, 'Jabalpur'),
(203, 20, 'Ujjain'),
(204, 20, 'Sagar'),
(205, 20, 'Satna'),
(206, 20, 'Rewa'),
(207, 20, 'Ratlam'),
(208, 20, 'Dewas'),
(209, 20, 'Chhindwara'),
(210, 20, 'Vidisha'),
(211, 20, 'Sehore'),
(212, 20, 'Betul'),
(213, 20, 'Hoshangabad'),
(214, 20, 'Khandwa'),
(215, 20, 'Khargone'),
(216, 21, 'Mumbai City'),
(217, 21, 'Mumbai Suburban'),
(218, 21, 'Pune'),
(219, 21, 'Nagpur'),
(220, 21, 'Nashik'),
(221, 21, 'Thane'),
(222, 21, 'Aurangabad'),
(223, 21, 'Solapur'),
(224, 21, 'Kolhapur'),
(225, 21, 'Amravati'),
(226, 21, 'Nanded'),
(227, 21, 'Sangli'),
(228, 21, 'Satara'),
(229, 21, 'Ahmednagar'),
(230, 21, 'Latur'),
(231, 21, 'Akola'),
(232, 21, 'Jalgaon'),
(233, 21, 'Raigad'),
(234, 22, 'Imphal East'),
(235, 22, 'Imphal West'),
(236, 22, 'Thoubal'),
(237, 22, 'Bishnupur'),
(238, 22, 'Churachandpur'),
(239, 22, 'Senapati'),
(240, 22, 'Ukhrul'),
(241, 22, 'Tamenglong'),
(242, 22, 'Chandel'),
(243, 23, 'East Khasi Hills'),
(244, 23, 'West Khasi Hills'),
(245, 23, 'Ri Bhoi'),
(246, 23, 'East Garo Hills'),
(247, 23, 'West Garo Hills'),
(248, 23, 'Jaintia Hills'),
(249, 24, 'Aizawl'),
(250, 24, 'Lunglei'),
(251, 24, 'Champhai'),
(252, 24, 'Kolasib'),
(253, 24, 'Mamit'),
(254, 24, 'Serchhip'),
(255, 24, 'Lawngtlai'),
(256, 24, 'Saiha'),
(257, 25, 'Kohima'),
(258, 25, 'Dimapur'),
(259, 25, 'Mokokchung'),
(260, 25, 'Tuensang'),
(261, 25, 'Wokha'),
(262, 25, 'Zunheboto'),
(263, 25, 'Mon'),
(264, 25, 'Phek'),
(265, 26, 'Bhubaneswar (Khordha)'),
(266, 26, 'Cuttack'),
(267, 26, 'Puri'),
(268, 26, 'Ganjam'),
(269, 26, 'Sambalpur'),
(270, 26, 'Rourkela (Sundargarh)'),
(271, 26, 'Balasore'),
(272, 26, 'Mayurbhanj'),
(273, 26, 'Kalahandi'),
(274, 26, 'Koraput'),
(275, 26, 'Bolangir'),
(276, 26, 'Angul'),
(277, 27, 'Puducherry'),
(278, 27, 'Karaikal'),
(279, 27, 'Mahe'),
(280, 27, 'Yanam'),
(281, 28, 'Ludhiana'),
(282, 28, 'Amritsar'),
(283, 28, 'Jalandhar'),
(284, 28, 'Patiala'),
(285, 28, 'Bathinda'),
(286, 28, 'Mohali'),
(287, 28, 'Hoshiarpur'),
(288, 28, 'Ferozepur'),
(289, 28, 'Moga'),
(290, 28, 'Sangrur'),
(291, 28, 'Kapurthala'),
(292, 28, 'Gurdaspur'),
(293, 29, 'Jaipur'),
(294, 29, 'Jodhpur'),
(295, 29, 'Udaipur'),
(296, 29, 'Kota'),
(297, 29, 'Ajmer'),
(298, 29, 'Bikaner'),
(299, 29, 'Alwar'),
(300, 29, 'Bharatpur'),
(301, 29, 'Sikar'),
(302, 29, 'Bhilwara'),
(303, 29, 'Pali'),
(304, 29, 'Sri Ganganagar'),
(305, 29, 'Nagaur'),
(306, 29, 'Churu'),
(307, 29, 'Jaisalmer'),
(308, 30, 'East Sikkim'),
(309, 30, 'West Sikkim'),
(310, 30, 'North Sikkim'),
(311, 30, 'South Sikkim'),
(312, 31, 'Chennai'),
(313, 31, 'Coimbatore'),
(314, 31, 'Madurai'),
(315, 31, 'Tiruchirappalli'),
(316, 31, 'Salem'),
(317, 31, 'Tirunelveli'),
(318, 31, 'Erode'),
(319, 31, 'Vellore'),
(320, 31, 'Thanjavur'),
(321, 31, 'Tiruppur'),
(322, 31, 'Dindigul'),
(323, 31, 'Kanchipuram'),
(324, 31, 'Cuddalore'),
(325, 31, 'Thoothukudi'),
(326, 31, 'Nagercoil (Kanyakumari)'),
(327, 32, 'Hyderabad'),
(328, 32, 'Warangal'),
(329, 32, 'Nizamabad'),
(330, 32, 'Karimnagar'),
(331, 32, 'Khammam'),
(332, 32, 'Ramagundam'),
(333, 32, 'Mahbubnagar'),
(334, 32, 'Nalgonda'),
(335, 32, 'Adilabad'),
(336, 32, 'Medak'),
(337, 32, 'Rangareddy'),
(338, 32, 'Siddipet'),
(339, 33, 'West Tripura'),
(340, 33, 'South Tripura'),
(341, 33, 'North Tripura'),
(342, 33, 'Dhalai'),
(343, 33, 'Khowai'),
(344, 33, 'Sepahijala'),
(345, 33, 'Gomati'),
(346, 33, 'Unakoti'),
(347, 34, 'Lucknow'),
(348, 34, 'Kanpur'),
(349, 34, 'Agra'),
(350, 34, 'Varanasi'),
(351, 34, 'Meerut'),
(352, 34, 'Prayagraj'),
(353, 34, 'Ghaziabad'),
(354, 34, 'Noida (Gautam Buddh Nagar)'),
(355, 34, 'Bareilly'),
(356, 34, 'Aligarh'),
(357, 34, 'Moradabad'),
(358, 34, 'Saharanpur'),
(359, 34, 'Gorakhpur'),
(360, 34, 'Jhansi'),
(361, 34, 'Mathura'),
(362, 34, 'Firozabad'),
(363, 34, 'Muzaffarnagar'),
(364, 35, 'Dehradun'),
(365, 35, 'Haridwar'),
(366, 35, 'Nainital'),
(367, 35, 'Udham Singh Nagar'),
(368, 35, 'Almora'),
(369, 35, 'Pauri Garhwal'),
(370, 35, 'Tehri Garhwal'),
(371, 35, 'Chamoli'),
(372, 35, 'Pithoragarh'),
(373, 35, 'Champawat'),
(374, 35, 'Bageshwar'),
(375, 35, 'Rudraprayag'),
(376, 35, 'Uttarkashi'),
(377, 36, 'Kolkata'),
(378, 36, 'Howrah'),
(379, 36, 'North 24 Parganas'),
(380, 36, 'South 24 Parganas'),
(381, 36, 'Hooghly'),
(382, 36, 'Nadia'),
(383, 36, 'Murshidabad'),
(384, 36, 'Purba Bardhaman'),
(385, 36, 'Paschim Bardhaman'),
(386, 36, 'Darjeeling'),
(387, 36, 'Malda'),
(388, 36, 'Jalpaiguri'),
(389, 36, 'Cooch Behar'),
(390, 36, 'Purulia'),
(391, 36, 'Bankura');

-- --------------------------------------------------------

--
-- Table structure for table `doctor_connections`
--

CREATE TABLE `doctor_connections` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `status` enum('pending','accepted','declined') NOT NULL DEFAULT 'pending',
  `message` varchar(255) DEFAULT NULL,
  `requested_at` datetime NOT NULL DEFAULT current_timestamp(),
  `responded_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `doctor_connections`
--

INSERT INTO `doctor_connections` (`id`, `patient_id`, `doctor_id`, `status`, `message`, `requested_at`, `responded_at`) VALUES
(1, 1, 101, 'accepted', 'euuebb', '2026-09-01 19:40:21', '2026-09-01 19:41:00'),
(2, 1, 102, 'accepted', 'ubhbh', '2026-09-02 19:24:28', '2026-09-02 19:25:19');

-- --------------------------------------------------------

--
-- Table structure for table `doctor_patient_notes`
--

CREATE TABLE `doctor_patient_notes` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `note` text NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dose_logs`
--

CREATE TABLE `dose_logs` (
  `id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `scheduled_for` datetime NOT NULL,
  `status` enum('upcoming','taken','missed') NOT NULL DEFAULT 'upcoming',
  `taken_at` datetime DEFAULT NULL,
  `snooze_count` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dose_logs`
--

INSERT INTO `dose_logs` (`id`, `schedule_id`, `scheduled_for`, `status`, `taken_at`, `snooze_count`, `created_at`) VALUES
(5, 4, '2026-09-02 19:40:00', 'taken', '2026-09-02 15:52:39', 0, '2026-09-02 19:22:29'),
(7, 6, '2026-09-02 19:25:00', 'taken', '2026-09-02 15:56:39', 0, '2026-09-02 19:23:46');

-- --------------------------------------------------------

--
-- Table structure for table `medicines`
--

CREATE TABLE `medicines` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `dosage` varchar(100) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `medicines`
--

INSERT INTO `medicines` (`id`, `user_id`, `name`, `dosage`, `notes`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'Paracetamole', '200mg', 'vvg', 1, '2026-09-01 19:38:07', '2026-09-02 19:22:29'),
(2, 1, 'Meta', '1000', 'hheh', 1, '2026-09-02 19:23:31', '2026-09-02 19:23:31');

-- --------------------------------------------------------

--
-- Table structure for table `medicine_schedules`
--

CREATE TABLE `medicine_schedules` (
  `id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `time_of_day` time NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `medicine_schedules`
--

INSERT INTO `medicine_schedules` (`id`, `medicine_id`, `time_of_day`, `is_active`, `created_at`) VALUES
(4, 1, '19:40:00', 1, '2026-09-02 19:22:29'),
(6, 2, '19:25:00', 1, '2026-09-02 19:23:46');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `connection_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `body` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `read_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `connection_id`, `sender_id`, `body`, `created_at`, `read_at`) VALUES
(1, 1, 1, 'hello doctor', '2026-09-01 19:41:13', '2026-09-01 19:41:16'),
(2, 1, 101, 'yes', '2026-09-01 19:41:21', '2026-09-01 19:41:59'),
(3, 2, 102, 'hey', '2026-09-02 19:25:33', '2026-09-02 19:25:40'),
(4, 2, 1, 'how you doing', '2026-09-02 19:25:40', '2026-09-02 19:26:07'),
(5, 2, 1, 'patient died', '2026-09-02 19:25:58', '2026-09-02 19:26:07'),
(6, 2, 102, 'clean it up solider', '2026-09-02 19:26:07', '2026-09-02 19:26:13'),
(7, 2, 1, 'yes sir', '2026-09-02 19:26:13', '2026-09-02 19:27:03');

-- --------------------------------------------------------

--
-- Table structure for table `prescriptions`
--

CREATE TABLE `prescriptions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `doctor_name` varchar(150) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_original_name` varchar(255) NOT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subject` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `status` enum('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
  `admin_reply` text DEFAULT NULL,
  `replied_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `states`
--

CREATE TABLE `states` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `states`
--

INSERT INTO `states` (`id`, `name`) VALUES
(1, 'Andaman and Nicobar Islands'),
(2, 'Andhra Pradesh'),
(3, 'Arunachal Pradesh'),
(4, 'Assam'),
(5, 'Bihar'),
(6, 'Chandigarh'),
(7, 'Chhattisgarh'),
(8, 'Dadra and Nagar Haveli and Daman and Diu'),
(9, 'Delhi'),
(10, 'Goa'),
(11, 'Gujarat'),
(12, 'Haryana'),
(13, 'Himachal Pradesh'),
(14, 'Jammu and Kashmir'),
(15, 'Jharkhand'),
(16, 'Karnataka'),
(17, 'Kerala'),
(18, 'Ladakh'),
(19, 'Lakshadweep'),
(20, 'Madhya Pradesh'),
(21, 'Maharashtra'),
(22, 'Manipur'),
(23, 'Meghalaya'),
(24, 'Mizoram'),
(25, 'Nagaland'),
(26, 'Odisha'),
(27, 'Puducherry'),
(28, 'Punjab'),
(29, 'Rajasthan'),
(30, 'Sikkim'),
(31, 'Tamil Nadu'),
(32, 'Telangana'),
(33, 'Tripura'),
(34, 'Uttar Pradesh'),
(35, 'Uttarakhand'),
(36, 'West Bengal');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` datetime DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `phone_verified_at` datetime DEFAULT NULL,
  `state_id` int(11) DEFAULT NULL,
  `district_id` int(11) DEFAULT NULL,
  `notify_email` tinyint(1) NOT NULL DEFAULT 1,
  `notify_sms` tinyint(1) NOT NULL DEFAULT 0,
  `specialty` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('patient','doctor','admin') NOT NULL,
  `status` enum('active','suspended','deactivated') NOT NULL DEFAULT 'active',
  `is_verified` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` datetime DEFAULT NULL,
  `failed_login_attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `phone`, `phone_verified_at`, `state_id`, `district_id`, `notify_email`, `notify_sms`, `specialty`, `password`, `role`, `status`, `is_verified`, `last_login_at`, `failed_login_attempts`, `locked_until`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Govind', 'govindmanoj333@gmail.com', '2026-07-01 19:06:52', '+917994107367', '2026-07-01 19:20:20', NULL, NULL, 1, 0, NULL, '12345677', 'patient', 'active', 1, NULL, 0, NULL, '2026-07-12 19:06:42', '2026-07-16 11:16:59', NULL),
(3, 'Test Patient', 'testpatient@example.com', NULL, '+919876543210', NULL, NULL, NULL, 1, 0, NULL, 'test1234', 'patient', 'active', 1, NULL, 0, NULL, '2026-07-12 19:28:07', '2026-07-12 19:28:07', NULL),
(4, 'Kohai', 'kohai79941@gmail.com', NULL, '+917994107367', NULL, NULL, NULL, 1, 0, NULL, '12341234', 'patient', 'active', 1, NULL, 0, NULL, '2026-07-12 19:55:49', '2026-07-12 19:55:49', NULL),
(101, 'Dr. Anjali Menon', 'anjali.menon@kare-demo.test', NULL, '+919812345601', NULL, NULL, NULL, 1, 0, 'General Physician', 'doctor1234', 'doctor', 'active', 1, NULL, 0, NULL, '2026-09-01 19:33:23', '2026-09-01 19:33:23', NULL),
(102, 'Dr. Rahul Nair', 'rahul.nair@kare-demo.test', NULL, '+919812345602', NULL, NULL, NULL, 1, 0, 'Cardiologist', 'doctor1234', 'doctor', 'active', 1, NULL, 0, NULL, '2026-09-01 19:33:23', '2026-09-01 19:33:23', NULL),
(103, 'Dr. Sara Thomas', 'sara.thomas@kare-demo.test', NULL, '+919812345603', NULL, NULL, NULL, 1, 0, 'Endocrinologist', 'doctor1234', 'doctor', 'active', 1, NULL, 0, NULL, '2026-09-01 19:33:23', '2026-09-01 19:33:23', NULL),
(201, 'Kare Admin', 'admin@kare-demo.test', NULL, '+919812345001', NULL, NULL, NULL, 1, 0, NULL, 'admin1234', 'admin', 'active', 1, NULL, 0, NULL, '2026-09-01 19:33:31', '2026-09-01 19:33:31', NULL),
(202, 'Demo', 'demo@centzcork.ie', NULL, '1234445646', NULL, NULL, NULL, 1, 0, NULL, 'demo123456', 'patient', 'active', 1, NULL, 0, NULL, '2026-09-02 19:28:42', '2026-09-02 19:28:42', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `districts`
--
ALTER TABLE `districts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_districts_state_id` (`state_id`);

--
-- Indexes for table `doctor_connections`
--
ALTER TABLE `doctor_connections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_patient_doctor` (`patient_id`,`doctor_id`),
  ADD KEY `idx_connections_doctor_id` (`doctor_id`);

--
-- Indexes for table `doctor_patient_notes`
--
ALTER TABLE `doctor_patient_notes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_doctor_patient` (`doctor_id`,`patient_id`),
  ADD KEY `idx_notes_patient_id` (`patient_id`);

--
-- Indexes for table `dose_logs`
--
ALTER TABLE `dose_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dose_logs_schedule_id` (`schedule_id`),
  ADD KEY `idx_dose_logs_scheduled_for` (`scheduled_for`);

--
-- Indexes for table `medicines`
--
ALTER TABLE `medicines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_medicines_user_id` (`user_id`);

--
-- Indexes for table `medicine_schedules`
--
ALTER TABLE `medicine_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_schedules_medicine_id` (`medicine_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_messages_connection_id` (`connection_id`),
  ADD KEY `fk_messages_sender` (`sender_id`);

--
-- Indexes for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_prescriptions_user_id` (`user_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reports_user_id` (`user_id`),
  ADD KEY `idx_reports_status` (`status`);

--
-- Indexes for table `states`
--
ALTER TABLE `states`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_states_name` (`name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD KEY `idx_users_role_status` (`role`,`status`),
  ADD KEY `idx_users_deleted_at` (`deleted_at`),
  ADD KEY `idx_users_state_id` (`state_id`),
  ADD KEY `idx_users_district_id` (`district_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `districts`
--
ALTER TABLE `districts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=392;

--
-- AUTO_INCREMENT for table `doctor_connections`
--
ALTER TABLE `doctor_connections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `doctor_patient_notes`
--
ALTER TABLE `doctor_patient_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dose_logs`
--
ALTER TABLE `dose_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `medicines`
--
ALTER TABLE `medicines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `medicine_schedules`
--
ALTER TABLE `medicine_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `states`
--
ALTER TABLE `states`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=203;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `districts`
--
ALTER TABLE `districts`
  ADD CONSTRAINT `fk_districts_state` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_connections`
--
ALTER TABLE `doctor_connections`
  ADD CONSTRAINT `fk_connections_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_connections_patient` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_patient_notes`
--
ALTER TABLE `doctor_patient_notes`
  ADD CONSTRAINT `fk_notes_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_notes_patient` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `dose_logs`
--
ALTER TABLE `dose_logs`
  ADD CONSTRAINT `fk_dose_logs_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `medicine_schedules` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `medicines`
--
ALTER TABLE `medicines`
  ADD CONSTRAINT `fk_medicines_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `medicine_schedules`
--
ALTER TABLE `medicine_schedules`
  ADD CONSTRAINT `fk_schedules_medicine` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_messages_connection` FOREIGN KEY (`connection_id`) REFERENCES `doctor_connections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD CONSTRAINT `fk_prescriptions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `fk_reports_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_district` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_users_state` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
