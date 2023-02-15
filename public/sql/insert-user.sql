INSERT INTO participant (site_id, email, roles, password, username, nom, prenom)
VALUES (1, 'admin@test.fr', '["ROLE_ADMIN"]', '$2y$13$7l6dvHMqnLw.pZGHQ8ps5ugRxbpGTO6p7JnEjkpqJAeXwOU7IinYO', 'Mel', 'De Bona', 'Julie'),
       (2, 'participante@test.fr', '[]', '$2y$13$gJX1GX.rUCKxDCEruXakveLLQYwMz0dlb2jQt4rnivbUeeTaK2o5y', 'uneParticipante', 'Perissé', 'Swann'),
       (3, 'organisatrice@test.fr', '[]', '$2y$13$MLJs0mEba2szBRLCUEcbdupY29eCLzmtILbbKcTiBeFIoXUN676R6', 'uneOrganisatrice', 'Veil', 'Simone')