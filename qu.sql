-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Dec 09, 2025 at 05:28 PM
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
-- Database: `qu`
--

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE `groups` (
  `group_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `group_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `groups`
--

INSERT INTO `groups` (`group_id`, `teacher_id`, `group_name`) VALUES
(1, 2, 'Grade 10 Math'),
(2, 2, 'Physics Lab Group'),
(3, 2, 'Advanced English'),
(4, 1, '232King'),
(5, 1, '232King'),
(6, 1, '232ITing');

-- --------------------------------------------------------

--
-- Table structure for table `options`
--

CREATE TABLE `options` (
  `option_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `option_a` varchar(1000) NOT NULL,
  `option_b` varchar(1000) NOT NULL,
  `option_c` varchar(1000) NOT NULL,
  `option_d` varchar(1000) NOT NULL,
  `correct_option` enum('option_a','option_b','option_c','option_d') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `question_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `option_a` varchar(255) NOT NULL,
  `option_b` varchar(255) NOT NULL,
  `option_c` varchar(255) NOT NULL,
  `option_d` varchar(255) NOT NULL,
  `correct_option` char(1) NOT NULL,
  `points` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`question_id`, `quiz_id`, `question_text`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_option`, `points`) VALUES
(1, 1, 'What is 5x + 3 = 18? Solve for x.', '3', '5', '4', '15', 'A', 1),
(2, 4, 'If a=4 and b=5, what is a*b - a?', '16', '20', '15', '19', 'C', 1),
(3, 5, 'Which language is PHP based on?', 'C++', 'Java', 'HTML', 'Perl', 'D', 1),
(4, 6, 'What does PDO stand for in PHP?', 'Portable Data Object', 'PHP Database Object', 'Persistent Data Output', 'None of the above', 'B', 1),
(5, 7, 'salam', 'asas', 'asasss', 'asassssssssssssss', 'asdsd', 'A', 1),
(6, 7, 'SALAMLAR', 'OPTION A', 'OPTION B', 'OPTION C', 'OPTION D', 'D', 1),
(7, 102, 'What does a variable store in programming?', 'Random numbers', 'A value that can change', 'Only text', 'Only numbers', 'B', 1),
(8, 102, 'Which symbol is used for assignment in most programming languages?', 'A: ==', 'B: =', 'C: :=', 'D: ->', 'B', 1),
(9, 102, 'What is an algorithm?', 'A hardware device', 'A programming language', 'A sequence of steps to solve a problem', 'A computer error', 'C', 1),
(10, 101, 'awsda', 'aaaaa', 'v', 'd', 'g', 'A', 1),
(11, 104, 'WCU', 'Western', 'Western Caspian', 'Western Caspian Uni', 'Western Caspian University', 'D', 1),
(12, 105, 'Nece bal qoyursuz mene', '10', '9', '8', '7', 'A', 1);

-- --------------------------------------------------------

--
-- Table structure for table `quizzes`
--

CREATE TABLE `quizzes` (
  `quiz_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `duration_minutes` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quizzes`
--

INSERT INTO `quizzes` (`quiz_id`, `teacher_id`, `subject_id`, `title`, `duration_minutes`, `created_at`) VALUES
(1, 2, 1, 'Algebra Basics Test', 45, '2025-12-05 00:44:04'),
(4, 1, 4, 'Mid1', 60, '2025-12-05 01:07:17'),
(5, 1, 4, 'Mid3', 60, '2025-12-05 01:08:31'),
(6, 1, 4, 'Mid2', 60, '2025-12-05 01:10:17'),
(7, 1, 4, 'Web Prog Exam', 60, '2025-12-05 01:21:51'),
(101, 1, 4, 'Web Programming', 60, '2025-12-05 08:28:45'),
(102, 1, 4, 'Test', 30, '2025-12-05 10:56:56'),
(103, 1, 5, 'Last', 10, '2025-12-05 22:19:49'),
(104, 1, 1, 'WCU', 60, '2025-12-09 10:54:12'),
(105, 1, 1, 'Telebe', 60, '2025-12-09 11:20:36');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_groupes`
--

CREATE TABLE `quiz_groupes` (
  `quiz_group_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `assignment_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quiz_groups`
--

CREATE TABLE `quiz_groups` (
  `quiz_group_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `assignment_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `quiz_groups`
--

INSERT INTO `quiz_groups` (`quiz_group_id`, `quiz_id`, `group_id`, `assignment_date`) VALUES
(4, 1, 1, '2025-10-15'),
(5, 7, 4, '2025-12-04'),
(6, 4, 4, '2025-12-05'),
(7, 4, 5, '2025-12-05'),
(8, 102, 6, '2025-12-05'),
(9, 101, 6, '2025-12-05'),
(10, 101, 4, '2025-12-05'),
(11, 101, 5, '2025-12-05'),
(12, 103, 6, '2025-12-05'),
(13, 4, 6, '2025-12-05'),
(14, 104, 6, '2025-12-09'),
(15, 104, 4, '2025-12-09'),
(16, 104, 5, '2025-12-09'),
(17, 105, 6, '2025-12-09'),
(18, 105, 4, '2025-12-09'),
(19, 105, 5, '2025-12-09');

-- --------------------------------------------------------

--
-- Table structure for table `results`
--

CREATE TABLE `results` (
  `result_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `score` decimal(5,2) NOT NULL,
  `total_points` int(11) NOT NULL,
  `submission_time` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `results`
--

INSERT INTO `results` (`result_id`, `quiz_id`, `student_id`, `score`, `total_points`, `submission_time`) VALUES
(3, 4, 3, 0.00, 1, '2025-12-05 09:31:40'),
(4, 7, 3, 0.00, 2, '2025-12-05 09:36:43'),
(5, 7, 3, 1.00, 2, '2025-12-05 23:30:00'),
(6, 7, 3, 0.00, 2, '2025-12-05 23:32:51'),
(7, 7, 3, 0.00, 2, '2025-12-05 23:33:06'),
(8, 7, 3, 1.00, 2, '2025-12-05 23:33:14'),
(9, 7, 3, 0.00, 2, '2025-12-05 23:36:56'),
(10, 7, 3, 1.00, 2, '2025-12-05 23:38:34'),
(11, 7, 3, 1.00, 2, '2025-12-05 23:39:30'),
(12, 7, 3, 1.00, 2, '2025-12-05 23:39:33'),
(13, 7, 3, 1.00, 2, '2025-12-05 23:41:09'),
(14, 101, 3, 1.00, 1, '2025-12-05 23:41:44'),
(15, 101, 3, 0.00, 1, '2025-12-05 23:41:51'),
(16, 102, 8, 2.00, 3, '2025-12-05 23:42:38'),
(17, 4, 8, 0.00, 1, '2025-12-05 23:44:48'),
(18, 101, 3, 1.00, 1, '2025-12-09 10:46:29'),
(19, 101, 3, 0.00, 1, '2025-12-09 10:46:36'),
(20, 104, 3, 0.00, 1, '2025-12-09 10:55:50'),
(21, 104, 3, 1.00, 1, '2025-12-09 10:55:56'),
(22, 105, 3, 0.00, 1, '2025-12-09 11:23:39'),
(23, 105, 3, 1.00, 1, '2025-12-09 11:24:03');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`) VALUES
(1, 'admin'),
(3, 'student'),
(2, 'teacher');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `subject_id` int(11) NOT NULL,
  `subject_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`subject_id`, `subject_name`) VALUES
(1, 'Mathematics'),
(4, 'Web Programming'),
(5, 'Web Programming systems');

-- --------------------------------------------------------

--
-- Table structure for table `submitted_answers`
--

CREATE TABLE `submitted_answers` (
  `submission_id` int(11) NOT NULL,
  `result_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `submitted_answer_value` varchar(10) NOT NULL,
  `is_correct` tinyint(1) NOT NULL,
  `student_answer` varchar(10) NOT NULL,
  `submission_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `submitted_answers`
--

INSERT INTO `submitted_answers` (`submission_id`, `result_id`, `question_id`, `submitted_answer_value`, `is_correct`, `student_answer`, `submission_time`) VALUES
(1, 13, 5, 'C', 0, 'C', '2025-12-05 19:41:09'),
(2, 13, 6, 'D', 1, 'D', '2025-12-05 19:41:09'),
(3, 14, 10, 'A', 1, 'A', '2025-12-05 19:41:44'),
(4, 15, 10, 'B', 0, 'B', '2025-12-05 19:41:51'),
(5, 16, 7, 'B', 1, 'B', '2025-12-05 19:42:38'),
(6, 16, 8, 'A', 0, 'A', '2025-12-05 19:42:38'),
(7, 16, 9, 'C', 1, 'C', '2025-12-05 19:42:38'),
(8, 17, 2, 'B', 0, 'B', '2025-12-05 19:44:48'),
(9, 18, 10, 'A', 1, 'A', '2025-12-09 06:46:29'),
(10, 19, 10, 'B', 0, 'B', '2025-12-09 06:46:36'),
(11, 20, 11, 'C', 0, 'C', '2025-12-09 06:55:50'),
(12, 21, 11, 'D', 1, 'D', '2025-12-09 06:55:56'),
(13, 22, 12, 'B', 0, 'B', '2025-12-09 07:23:39'),
(14, 23, 12, 'A', 1, 'A', '2025-12-09 07:24:03');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `group_number` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `subject_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role_id`, `username`, `email`, `password`, `first_name`, `last_name`, `is_active`, `group_number`, `created_at`, `updated_at`, `subject_id`) VALUES
(1, 2, 'VafaBaghirova', NULL, 'vafa1998', NULL, NULL, 1, NULL, '2025-12-04 20:44:04', '2025-12-05 17:03:49', NULL),
(2, 1, 'admin', 'admin', 'admin', NULL, NULL, 1, NULL, '2025-12-04 20:44:04', '2025-12-05 17:03:49', NULL),
(3, 3, 'Murad', 'murad', 'murad', NULL, NULL, 1, '232King', '2025-12-04 20:44:04', '2025-12-05 17:03:49', NULL),
(4, 2, 'Test', 'Test@teacher.local', 'testtest', 'Teacher', 'Test', 1, NULL, '2025-12-05 17:32:40', '2025-12-05 17:32:40', NULL),
(8, 3, 'farid', 'farid@student.local', 'farid', 'Student', 'farid', 1, '232ITing', '2025-12-05 17:37:37', '2025-12-05 17:37:37', 5);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`group_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `options`
--
ALTER TABLE `options`
  ADD PRIMARY KEY (`option_id`),
  ADD UNIQUE KEY `question_id` (`question_id`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`question_id`),
  ADD KEY `quiz_id` (`quiz_id`);

--
-- Indexes for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD PRIMARY KEY (`quiz_id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `quiz_groupes`
--
ALTER TABLE `quiz_groupes`
  ADD PRIMARY KEY (`quiz_group_id`),
  ADD KEY `quiz_id` (`quiz_id`),
  ADD KEY `group_id` (`group_id`);

--
-- Indexes for table `quiz_groups`
--
ALTER TABLE `quiz_groups`
  ADD PRIMARY KEY (`quiz_group_id`),
  ADD KEY `quiz_id` (`quiz_id`),
  ADD KEY `group_id` (`group_id`);

--
-- Indexes for table `results`
--
ALTER TABLE `results`
  ADD PRIMARY KEY (`result_id`),
  ADD KEY `quiz_id` (`quiz_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`subject_id`),
  ADD UNIQUE KEY `subject_name` (`subject_name`);

--
-- Indexes for table `submitted_answers`
--
ALTER TABLE `submitted_answers`
  ADD PRIMARY KEY (`submission_id`),
  ADD UNIQUE KEY `unique_submission` (`result_id`,`question_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `username_2` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `group_number` (`group_number`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `groups`
--
ALTER TABLE `groups`
  MODIFY `group_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `options`
--
ALTER TABLE `options`
  MODIFY `option_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `quizzes`
--
ALTER TABLE `quizzes`
  MODIFY `quiz_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- AUTO_INCREMENT for table `quiz_groupes`
--
ALTER TABLE `quiz_groupes`
  MODIFY `quiz_group_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quiz_groups`
--
ALTER TABLE `quiz_groups`
  MODIFY `quiz_group_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `results`
--
ALTER TABLE `results`
  MODIFY `result_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `submitted_answers`
--
ALTER TABLE `submitted_answers`
  MODIFY `submission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `groups`
--
ALTER TABLE `groups`
  ADD CONSTRAINT `groups_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `options`
--
ALTER TABLE `options`
  ADD CONSTRAINT `options_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`question_id`) ON DELETE CASCADE;

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`quiz_id`) ON DELETE CASCADE;

--
-- Constraints for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD CONSTRAINT `quizzes_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quizzes_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`);

--
-- Constraints for table `quiz_groupes`
--
ALTER TABLE `quiz_groupes`
  ADD CONSTRAINT `quiz_groupes_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`quiz_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quiz_groupes_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `groups` (`group_id`) ON DELETE CASCADE;

--
-- Constraints for table `quiz_groups`
--
ALTER TABLE `quiz_groups`
  ADD CONSTRAINT `quiz_groups_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`quiz_id`),
  ADD CONSTRAINT `quiz_groups_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `groups` (`group_id`);

--
-- Constraints for table `results`
--
ALTER TABLE `results`
  ADD CONSTRAINT `results_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`quiz_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `results_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `submitted_answers`
--
ALTER TABLE `submitted_answers`
  ADD CONSTRAINT `submitted_answers_ibfk_1` FOREIGN KEY (`result_id`) REFERENCES `results` (`result_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `submitted_answers_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `questions` (`question_id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
