--
-- Table structure for table `pengetahuan`
--

CREATE TABLE `pengetahuan` (
  `id` varchar(100) NOT NULL,
  `token` varchar(50) NOT NULL,
  `context` text NOT NULL,
  `fact` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengetahuan`
--

INSERT INTO `pengetahuan` (`id`, `token`, `context`, `fact`) VALUES
('AI_1772469377', 'Wikipedia', 'internet, encyclopedia', 'A free, multilingual online encyclopedia written and maintained by a community of volunteers through open collaboration.'),
('AI_1772470308', 'ChatGPT', 'AI, NLP, OpenAI', 'ChatGPT is an artificial intelligence chatbot developed by OpenAI that uses large language models to interact in a conversational way.'),
('AI_1772470667', 'Indonesia', 'Southeast Asia, Oceania', 'An archipelago located between the Indian and Pacific Oceans.'),
('AI_1772471579', 'Earth', 'Astronomy, Planetary Science', 'Earth\'s scientific names are Terra or Tellus, though it is internationally recognized by its common name in astronomical and geological fields.'),
('AI_1772472108', 'speed_of_light', 'physics, vacuum', 'The speed of light in a vacuum is exactly 299,792,458 meters per second, often approximated as 300,000 kilometers per second.'),
('coding', 'php', 'code, script, backend, server, logic', 'PHP is the backbone of the web. Are you using version 8.x?'),
('coffee', 'hot', 'drink, latte, brew, cup, caffeine, tea', 'Careful! Freshly brewed coffee is usually served at 80°C.'),
('weather', 'hot', 'sun, bali, outside, forecast, temperature', 'It is scorching in Bali! Stay hydrated and wear sunscreen.');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `pengetahuan`
--
ALTER TABLE `pengetahuan`
  ADD PRIMARY KEY (`id`);
COMMIT;
