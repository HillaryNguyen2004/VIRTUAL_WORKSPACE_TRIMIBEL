SET FOREIGN_KEY_CHECKS=0;
TRUNCATE TABLE tasks;
TRUNCATE TABLE phases;
TRUNCATE TABLE projects;
SET FOREIGN_KEY_CHECKS=1;


        INSERT INTO projects (id, title, staff_id, status, percentage, start_date, due_date)
        VALUES (1, 'Scalable Pipeline 1', 4, 'active', 67, '2024-05-22', '2024-12-14');
        

            INSERT INTO phases (id, project_id, title)
            VALUES (1, 1, 'Deployment');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1, 'Test UI', 27, 'in_progress', 'normal', 63, 0, 1, 1);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (2, 'Sub - Fix', 13, 'in_progress', 'urgent', 50, 0, 1, 1, 1);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (3, 'Sub - Optimize', 27, 'in_progress', 'low', 75, 0, 1, 1, 1);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (4, 'Design UI', 20, 'in_progress', 'urgent', 69, 0, 1, 1);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (5, 'Sub - Review', 26, 'pending', 'high', 0, 0, 1, 1, 4);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (6, 'Sub - Implement', 27, 'completed', 'low', 100, 94, 1, 1, 4);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (7, 'Sub - Review', 26, 'completed', 'high', 100, 54, 1, 1, 4);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (8, 'Sub - Analyze', 26, 'in_progress', 'high', 75, 0, 1, 1, 4);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (9, 'Test UI', 20, 'in_progress', 'low', 63, 0, 1, 1);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (10, 'Sub - Design', 20, 'completed', 'urgent', 100, 91, 1, 1, 9);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (11, 'Sub - Refactor', 27, 'in_progress', 'low', 25, 0, 1, 1, 9);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (12, 'Document API', 27, 'completed', 'high', 100, 79, 1, 1);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (13, 'Sub - Design', 4, 'completed', 'urgent', 100, 74, 1, 1, 12);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (14, 'Review service', 4, 'in_progress', 'low', 57, 0, 1, 1);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (15, 'Sub - Analyze', 27, 'in_progress', 'normal', 75, 0, 1, 1, 14);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (16, 'Sub - Optimize', 19, 'pending', 'normal', 0, 0, 1, 1, 14);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (17, 'Sub - Document', 26, 'in_progress', 'low', 50, 0, 1, 1, 14);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (18, 'Sub - Analyze', 20, 'completed', 'urgent', 100, 63, 1, 1, 14);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (19, 'Refactor module', 21, 'in_progress', 'low', 13, 0, 1, 1);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (20, 'Sub - Document', 26, 'pending', 'high', 0, 0, 1, 1, 19);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (21, 'Sub - Fix', 21, 'in_progress', 'low', 25, 0, 1, 1, 19);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (22, 'Document module', 20, 'in_progress', 'high', 44, 0, 1, 1);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (23, 'Sub - Analyze', 20, 'in_progress', 'low', 25, 0, 1, 1, 22);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (24, 'Sub - Fix', 21, 'in_progress', 'normal', 50, 0, 1, 1, 22);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (25, 'Sub - Review', 20, 'pending', 'normal', 0, 0, 1, 1, 22);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (26, 'Sub - Analyze', 13, 'completed', 'high', 100, 70, 1, 1, 22);
                    

            INSERT INTO phases (id, project_id, title)
            VALUES (2, 1, 'Design');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (27, 'Document workflow', 4, 'in_progress', 'low', 50, 0, 1, 2);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (28, 'Sub - Design', 19, 'pending', 'low', 0, 0, 1, 2, 27);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (29, 'Sub - Fix', 27, 'completed', 'low', 100, 53, 1, 2, 27);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (30, 'Sub - Document', 20, 'in_progress', 'high', 50, 0, 1, 2, 27);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (31, 'Refactor UI', 27, 'in_progress', 'urgent', 42, 0, 1, 2);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (32, 'Sub - Analyze', 4, 'pending', 'high', 0, 0, 1, 2, 31);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (33, 'Sub - Test', 20, 'completed', 'normal', 100, 52, 1, 2, 31);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (34, 'Sub - Analyze', 19, 'in_progress', 'normal', 25, 0, 1, 2, 31);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (35, 'Review pipeline', 20, 'completed', 'low', 100, 90, 1, 2);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (36, 'Sub - Review', 20, 'completed', 'normal', 100, 84, 1, 2, 35);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (37, 'Sub - Refactor', 27, 'completed', 'normal', 100, 83, 1, 2, 35);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (38, 'Design workflow', 4, 'in_progress', 'normal', 69, 0, 1, 2);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (39, 'Sub - Review', 4, 'in_progress', 'low', 50, 0, 1, 2, 38);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (40, 'Sub - Document', 26, 'in_progress', 'normal', 25, 0, 1, 2, 38);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (41, 'Sub - Refactor', 19, 'completed', 'normal', 100, 97, 1, 2, 38);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (42, 'Sub - Optimize', 27, 'completed', 'high', 100, 54, 1, 2, 38);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (43, 'Deploy workflow', 27, 'completed', 'urgent', 100, 90, 1, 2);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (44, 'Sub - Optimize', 19, 'completed', 'low', 100, 79, 1, 2, 43);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (45, 'Design endpoint', 27, 'completed', 'normal', 100, 88, 1, 2);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (46, 'Sub - Implement', 19, 'completed', 'high', 100, 80, 1, 2, 45);
                    

            INSERT INTO phases (id, project_id, title)
            VALUES (3, 1, 'Testing');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (47, 'Analyze pipeline', 27, 'in_progress', 'low', 67, 0, 1, 3);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (48, 'Sub - Fix', 13, 'in_progress', 'low', 50, 0, 1, 3, 47);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (49, 'Sub - Implement', 19, 'in_progress', 'high', 50, 0, 1, 3, 47);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (50, 'Sub - Document', 19, 'completed', 'high', 100, 67, 1, 3, 47);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (51, 'Test pipeline', 26, 'pending', 'low', 0, 0, 1, 3);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (52, 'Sub - Deploy', 4, 'pending', 'normal', 0, 0, 1, 3, 51);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (53, 'Sub - Refactor', 27, 'pending', 'urgent', 0, 0, 1, 3, 51);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (54, 'Document module', 19, 'completed', 'low', 100, 65, 1, 3);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (55, 'Sub - Review', 21, 'completed', 'normal', 100, 86, 1, 3, 54);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (56, 'Implement UI', 13, 'in_progress', 'normal', 50, 0, 1, 3);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (57, 'Sub - Document', 27, 'pending', 'normal', 0, 0, 1, 3, 56);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (58, 'Sub - Fix', 19, 'in_progress', 'normal', 50, 0, 1, 3, 56);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (59, 'Sub - Deploy', 4, 'in_progress', 'high', 50, 0, 1, 3, 56);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (60, 'Sub - Design', 27, 'completed', 'high', 100, 86, 1, 3, 56);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (61, 'Fix service', 21, 'in_progress', 'normal', 82, 0, 1, 3);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (62, 'Sub - Implement', 19, 'in_progress', 'high', 50, 0, 1, 3, 61);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (63, 'Sub - Analyze', 4, 'completed', 'normal', 100, 67, 1, 3, 61);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (64, 'Sub - Implement', 26, 'in_progress', 'low', 75, 0, 1, 3, 61);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (65, 'Sub - Refactor', 20, 'completed', 'low', 100, 63, 1, 3, 61);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (66, 'Document service', 13, 'in_progress', 'high', 50, 0, 1, 3);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (67, 'Sub - Analyze', 26, 'in_progress', 'low', 25, 0, 1, 3, 66);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (68, 'Sub - Fix', 26, 'in_progress', 'low', 75, 0, 1, 3, 66);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (69, 'Design API', 27, 'in_progress', 'low', 38, 0, 1, 3);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (70, 'Sub - Fix', 19, 'in_progress', 'high', 75, 0, 1, 3, 69);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (71, 'Sub - Document', 4, 'pending', 'urgent', 0, 0, 1, 3, 69);
                    

            INSERT INTO phases (id, project_id, title)
            VALUES (4, 1, 'Planning');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (72, 'Review dashboard', 20, 'in_progress', 'normal', 63, 0, 1, 4);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (73, 'Sub - Fix', 27, 'completed', 'normal', 100, 89, 1, 4, 72);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (74, 'Sub - Design', 26, 'in_progress', 'urgent', 25, 0, 1, 4, 72);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (75, 'Optimize UI', 13, 'in_progress', 'urgent', 50, 0, 1, 4);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (76, 'Sub - Deploy', 20, 'completed', 'low', 100, 86, 1, 4, 75);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (77, 'Sub - Implement', 13, 'pending', 'normal', 0, 0, 1, 4, 75);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (78, 'Document endpoint', 21, 'in_progress', 'urgent', 88, 0, 1, 4);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (79, 'Sub - Design', 20, 'in_progress', 'low', 75, 0, 1, 4, 78);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (80, 'Sub - Refactor', 4, 'in_progress', 'high', 75, 0, 1, 4, 78);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (81, 'Sub - Test', 26, 'completed', 'normal', 100, 88, 1, 4, 78);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (82, 'Sub - Refactor', 20, 'completed', 'low', 100, 86, 1, 4, 78);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (83, 'Document module', 21, 'completed', 'urgent', 100, 80, 1, 4);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (84, 'Sub - Fix', 19, 'completed', 'urgent', 100, 94, 1, 4, 83);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (85, 'Analyze API', 26, 'in_progress', 'high', 75, 0, 1, 4);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (86, 'Sub - Document', 4, 'in_progress', 'high', 75, 0, 1, 4, 85);
                    

        INSERT INTO projects (id, title, staff_id, status, percentage, start_date, due_date)
        VALUES (2, 'Smart Dashboard 2', 3, 'active', 60, '2024-09-16', '2025-08-19');
        

            INSERT INTO phases (id, project_id, title)
            VALUES (5, 2, 'Planning');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (87, 'Document dashboard', 29, 'in_progress', 'urgent', 44, 0, 2, 5);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (88, 'Sub - Review', 10, 'pending', 'normal', 0, 0, 2, 5, 87);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (89, 'Sub - Analyze', 10, 'in_progress', 'high', 75, 0, 2, 5, 87);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (90, 'Sub - Design', 10, 'pending', 'high', 0, 0, 2, 5, 87);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (91, 'Sub - Design', 11, 'completed', 'low', 100, 71, 2, 5, 87);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (92, 'Test service', 3, 'in_progress', 'high', 75, 0, 2, 5);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (93, 'Sub - Implement', 8, 'in_progress', 'high', 50, 0, 2, 5, 92);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (94, 'Sub - Analyze', 22, 'completed', 'normal', 100, 89, 2, 5, 92);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (95, 'Optimize module', 25, 'in_progress', 'low', 50, 0, 2, 5);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (96, 'Sub - Test', 29, 'pending', 'high', 0, 0, 2, 5, 95);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (97, 'Sub - Test', 11, 'in_progress', 'urgent', 50, 0, 2, 5, 95);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (98, 'Sub - Analyze', 3, 'in_progress', 'normal', 50, 0, 2, 5, 95);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (99, 'Sub - Test', 3, 'completed', 'low', 100, 67, 2, 5, 95);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (100, 'Deploy service', 8, 'in_progress', 'low', 57, 0, 2, 5);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (101, 'Sub - Fix', 10, 'in_progress', 'low', 25, 0, 2, 5, 100);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (102, 'Sub - Refactor', 29, 'in_progress', 'low', 75, 0, 2, 5, 100);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (103, 'Sub - Refactor', 3, 'in_progress', 'urgent', 25, 0, 2, 5, 100);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (104, 'Sub - Test', 25, 'completed', 'high', 100, 50, 2, 5, 100);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (105, 'Fix UI', 22, 'in_progress', 'low', 69, 0, 2, 5);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (106, 'Sub - Fix', 8, 'in_progress', 'low', 25, 0, 2, 5, 105);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (107, 'Sub - Implement', 25, 'in_progress', 'low', 50, 0, 2, 5, 105);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (108, 'Sub - Test', 22, 'completed', 'high', 100, 55, 2, 5, 105);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (109, 'Sub - Fix', 8, 'completed', 'normal', 100, 82, 2, 5, 105);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (110, 'Refactor API', 29, 'in_progress', 'normal', 75, 0, 2, 5);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (111, 'Sub - Review', 10, 'in_progress', 'high', 75, 0, 2, 5, 110);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (112, 'Optimize module', 25, 'in_progress', 'normal', 82, 0, 2, 5);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (113, 'Sub - Refactor', 29, 'completed', 'normal', 100, 93, 2, 5, 112);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (114, 'Sub - Test', 25, 'in_progress', 'low', 25, 0, 2, 5, 112);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (115, 'Sub - Optimize', 10, 'completed', 'low', 100, 59, 2, 5, 112);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (116, 'Sub - Document', 22, 'completed', 'normal', 100, 58, 2, 5, 112);
                    

            INSERT INTO phases (id, project_id, title)
            VALUES (6, 2, 'Development');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (117, 'Analyze dashboard', 3, 'in_progress', 'normal', 59, 0, 2, 6);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (118, 'Sub - Refactor', 25, 'completed', 'low', 100, 80, 2, 6, 117);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (119, 'Sub - Analyze', 22, 'pending', 'urgent', 0, 0, 2, 6, 117);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (120, 'Sub - Review', 8, 'in_progress', 'urgent', 75, 0, 2, 6, 117);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (121, 'Document service', 25, 'in_progress', 'low', 50, 0, 2, 6);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (122, 'Sub - Implement', 29, 'in_progress', 'low', 25, 0, 2, 6, 121);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (123, 'Sub - Optimize', 10, 'completed', 'low', 100, 79, 2, 6, 121);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (124, 'Sub - Analyze', 25, 'in_progress', 'high', 25, 0, 2, 6, 121);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (125, 'Deploy service', 3, 'in_progress', 'low', 57, 0, 2, 6);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (126, 'Sub - Document', 10, 'in_progress', 'normal', 25, 0, 2, 6, 125);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (127, 'Sub - Implement', 10, 'in_progress', 'low', 75, 0, 2, 6, 125);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (128, 'Sub - Review', 11, 'in_progress', 'high', 25, 0, 2, 6, 125);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (129, 'Sub - Fix', 29, 'completed', 'low', 100, 64, 2, 6, 125);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (130, 'Design pipeline', 3, 'in_progress', 'high', 9, 0, 2, 6);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (131, 'Sub - Deploy', 8, 'pending', 'normal', 0, 0, 2, 6, 130);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (132, 'Sub - Implement', 22, 'pending', 'urgent', 0, 0, 2, 6, 130);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (133, 'Sub - Review', 8, 'in_progress', 'urgent', 25, 0, 2, 6, 130);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (134, 'Analyze pipeline', 3, 'in_progress', 'urgent', 75, 0, 2, 6);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (135, 'Sub - Document', 29, 'in_progress', 'low', 75, 0, 2, 6, 134);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (136, 'Refactor pipeline', 25, 'in_progress', 'high', 50, 0, 2, 6);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (137, 'Sub - Analyze', 10, 'completed', 'high', 100, 72, 2, 6, 136);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (138, 'Sub - Document', 22, 'completed', 'normal', 100, 93, 2, 6, 136);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (139, 'Sub - Analyze', 11, 'pending', 'high', 0, 0, 2, 6, 136);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (140, 'Sub - Optimize', 29, 'pending', 'urgent', 0, 0, 2, 6, 136);
                    

            INSERT INTO phases (id, project_id, title)
            VALUES (7, 2, 'Planning');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (141, 'Implement API', 10, 'completed', 'urgent', 100, 71, 2, 7);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (142, 'Sub - Deploy', 11, 'completed', 'urgent', 100, 64, 2, 7, 141);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (143, 'Sub - Optimize', 22, 'completed', 'high', 100, 84, 2, 7, 141);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (144, 'Sub - Refactor', 8, 'completed', 'normal', 100, 59, 2, 7, 141);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (145, 'Sub - Review', 8, 'completed', 'normal', 100, 55, 2, 7, 141);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (146, 'Deploy module', 29, 'in_progress', 'low', 57, 0, 2, 7);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (147, 'Sub - Test', 25, 'in_progress', 'low', 75, 0, 2, 7, 146);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (148, 'Sub - Implement', 25, 'in_progress', 'low', 50, 0, 2, 7, 146);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (149, 'Sub - Test', 3, 'in_progress', 'high', 50, 0, 2, 7, 146);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (150, 'Sub - Document', 10, 'in_progress', 'normal', 50, 0, 2, 7, 146);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (151, 'Optimize module', 10, 'in_progress', 'urgent', 50, 0, 2, 7);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (152, 'Sub - Document', 22, 'in_progress', 'low', 50, 0, 2, 7, 151);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (153, 'Sub - Refactor', 22, 'completed', 'low', 100, 53, 2, 7, 151);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (154, 'Sub - Document', 25, 'pending', 'urgent', 0, 0, 2, 7, 151);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (155, 'Document database', 10, 'in_progress', 'urgent', 17, 0, 2, 7);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (156, 'Sub - Deploy', 29, 'in_progress', 'high', 25, 0, 2, 7, 155);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (157, 'Sub - Fix', 25, 'pending', 'high', 0, 0, 2, 7, 155);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (158, 'Sub - Fix', 8, 'in_progress', 'low', 25, 0, 2, 7, 155);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (159, 'Fix service', 8, 'in_progress', 'normal', 92, 0, 2, 7);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (160, 'Sub - Design', 22, 'completed', 'normal', 100, 61, 2, 7, 159);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (161, 'Sub - Review', 25, 'in_progress', 'high', 75, 0, 2, 7, 159);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (162, 'Sub - Review', 8, 'completed', 'high', 100, 99, 2, 7, 159);
                    

        INSERT INTO projects (id, title, staff_id, status, percentage, start_date, due_date)
        VALUES (3, 'Advanced Pipeline 3', 2, 'active', 61, '2024-12-20', '2025-04-07');
        

            INSERT INTO phases (id, project_id, title)
            VALUES (8, 3, 'Deployment');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (163, 'Refactor module', 12, 'in_progress', 'normal', 59, 0, 3, 8);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (164, 'Sub - Design', 2, 'completed', 'normal', 100, 73, 3, 8, 163);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (165, 'Sub - Document', 15, 'pending', 'high', 0, 0, 3, 8, 163);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (166, 'Sub - Optimize', 18, 'in_progress', 'urgent', 75, 0, 3, 8, 163);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (167, 'Review dashboard', 18, 'in_progress', 'normal', 42, 0, 3, 8);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (168, 'Sub - Deploy', 17, 'in_progress', 'low', 25, 0, 3, 8, 167);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (169, 'Sub - Implement', 15, 'completed', 'normal', 100, 82, 3, 8, 167);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (170, 'Sub - Document', 17, 'pending', 'urgent', 0, 0, 3, 8, 167);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (171, 'Analyze API', 2, 'completed', 'high', 100, 82, 3, 8);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (172, 'Sub - Deploy', 15, 'completed', 'urgent', 100, 78, 3, 8, 171);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (173, 'Sub - Deploy', 17, 'completed', 'low', 100, 80, 3, 8, 171);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (174, 'Analyze UI', 12, 'in_progress', 'normal', 75, 0, 3, 8);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (175, 'Sub - Implement', 18, 'completed', 'low', 100, 66, 3, 8, 174);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (176, 'Sub - Review', 18, 'in_progress', 'urgent', 25, 0, 3, 8, 174);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (177, 'Sub - Fix', 2, 'completed', 'low', 100, 91, 3, 8, 174);
                    

            INSERT INTO phases (id, project_id, title)
            VALUES (9, 3, 'Development');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (178, 'Document endpoint', 18, 'in_progress', 'normal', 50, 0, 3, 9);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (179, 'Sub - Optimize', 12, 'pending', 'normal', 0, 0, 3, 9, 178);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (180, 'Sub - Review', 12, 'completed', 'normal', 100, 52, 3, 9, 178);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (181, 'Optimize UI', 15, 'in_progress', 'normal', 25, 0, 3, 9);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (182, 'Sub - Deploy', 17, 'in_progress', 'low', 25, 0, 3, 9, 181);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (183, 'Optimize service', 18, 'in_progress', 'urgent', 75, 0, 3, 9);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (184, 'Sub - Refactor', 12, 'in_progress', 'urgent', 75, 0, 3, 9, 183);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (185, 'Sub - Implement', 17, 'in_progress', 'high', 50, 0, 3, 9, 183);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (186, 'Sub - Optimize', 2, 'completed', 'low', 100, 71, 3, 9, 183);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (187, 'Deploy database', 18, 'in_progress', 'normal', 34, 0, 3, 9);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (188, 'Sub - Refactor', 15, 'pending', 'normal', 0, 0, 3, 9, 187);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (189, 'Sub - Deploy', 2, 'in_progress', 'normal', 25, 0, 3, 9, 187);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (190, 'Sub - Fix', 12, 'in_progress', 'normal', 75, 0, 3, 9, 187);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (191, 'Document UI', 15, 'in_progress', 'low', 50, 0, 3, 9);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (192, 'Sub - Fix', 2, 'completed', 'normal', 100, 97, 3, 9, 191);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (193, 'Sub - Deploy', 12, 'pending', 'low', 0, 0, 3, 9, 191);
                    

            INSERT INTO phases (id, project_id, title)
            VALUES (10, 3, 'Design');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (194, 'Fix dashboard', 18, 'in_progress', 'urgent', 63, 0, 3, 10);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (195, 'Sub - Implement', 17, 'in_progress', 'low', 25, 0, 3, 10, 194);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (196, 'Sub - Refactor', 15, 'completed', 'low', 100, 98, 3, 10, 194);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (197, 'Implement endpoint', 12, 'in_progress', 'low', 25, 0, 3, 10);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (198, 'Sub - Test', 12, 'in_progress', 'low', 25, 0, 3, 10, 197);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (199, 'Design module', 15, 'completed', 'high', 100, 84, 3, 10);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (200, 'Sub - Design', 18, 'completed', 'high', 100, 83, 3, 10, 199);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (201, 'Fix module', 12, 'in_progress', 'high', 75, 0, 3, 10);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (202, 'Sub - Analyze', 15, 'in_progress', 'normal', 50, 0, 3, 10, 201);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (203, 'Sub - Document', 2, 'in_progress', 'urgent', 75, 0, 3, 10, 201);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (204, 'Sub - Optimize', 17, 'completed', 'low', 100, 98, 3, 10, 201);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (205, 'Analyze endpoint', 18, 'in_progress', 'normal', 67, 0, 3, 10);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (206, 'Sub - Deploy', 15, 'in_progress', 'normal', 75, 0, 3, 10, 205);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (207, 'Sub - Fix', 17, 'in_progress', 'low', 50, 0, 3, 10, 205);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (208, 'Sub - Fix', 17, 'in_progress', 'high', 75, 0, 3, 10, 205);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (209, 'Analyze dashboard', 15, 'in_progress', 'normal', 67, 0, 3, 10);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (210, 'Sub - Optimize', 18, 'pending', 'high', 0, 0, 3, 10, 209);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (211, 'Sub - Implement', 18, 'completed', 'normal', 100, 72, 3, 10, 209);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (212, 'Sub - Analyze', 2, 'completed', 'high', 100, 79, 3, 10, 209);
                    

        INSERT INTO projects (id, title, staff_id, status, percentage, start_date, due_date)
        VALUES (4, 'Scalable System 4', 6, 'active', 76, '2022-08-25', '2023-02-12');
        

            INSERT INTO phases (id, project_id, title)
            VALUES (11, 4, 'Design');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (213, 'Refactor service', 30, 'in_progress', 'low', 75, 0, 4, 11);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (214, 'Sub - Implement', 24, 'in_progress', 'urgent', 75, 0, 4, 11, 213);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (215, 'Design endpoint', 6, 'completed', 'low', 100, 69, 4, 11);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (216, 'Sub - Analyze', 16, 'completed', 'high', 100, 67, 4, 11, 215);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (217, 'Sub - Review', 16, 'completed', 'high', 100, 77, 4, 11, 215);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (218, 'Analyze workflow', 6, 'in_progress', 'high', 84, 0, 4, 11);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (219, 'Sub - Review', 16, 'completed', 'normal', 100, 79, 4, 11, 218);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (220, 'Sub - Refactor', 16, 'completed', 'low', 100, 60, 4, 11, 218);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (221, 'Sub - Review', 6, 'in_progress', 'urgent', 50, 0, 4, 11, 218);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (222, 'Document database', 6, 'in_progress', 'low', 67, 0, 4, 11);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (223, 'Sub - Refactor', 16, 'completed', 'urgent', 100, 66, 4, 11, 222);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (224, 'Sub - Refactor', 24, 'completed', 'urgent', 100, 79, 4, 11, 222);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (225, 'Sub - Document', 7, 'pending', 'low', 0, 0, 4, 11, 222);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (226, 'Test database', 30, 'in_progress', 'urgent', 57, 0, 4, 11);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (227, 'Sub - Optimize', 24, 'in_progress', 'urgent', 25, 0, 4, 11, 226);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (228, 'Sub - Test', 30, 'completed', 'normal', 100, 51, 4, 11, 226);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (229, 'Sub - Document', 24, 'pending', 'urgent', 0, 0, 4, 11, 226);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (230, 'Sub - Review', 7, 'completed', 'urgent', 100, 96, 4, 11, 226);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (231, 'Implement UI', 7, 'in_progress', 'high', 67, 0, 4, 11);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (232, 'Sub - Fix', 30, 'pending', 'urgent', 0, 0, 4, 11, 231);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (233, 'Sub - Deploy', 30, 'completed', 'normal', 100, 58, 4, 11, 231);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (234, 'Sub - Optimize', 7, 'completed', 'low', 100, 87, 4, 11, 231);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (235, 'Review API', 7, 'in_progress', 'high', 75, 0, 4, 11);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (236, 'Sub - Design', 24, 'in_progress', 'normal', 75, 0, 4, 11, 235);
                    

            INSERT INTO phases (id, project_id, title)
            VALUES (12, 4, 'Development');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (237, 'Deploy pipeline', 7, 'in_progress', 'low', 75, 0, 4, 12);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (238, 'Sub - Fix', 6, 'in_progress', 'urgent', 50, 0, 4, 12, 237);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (239, 'Sub - Test', 7, 'completed', 'normal', 100, 50, 4, 12, 237);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (240, 'Optimize endpoint', 7, 'in_progress', 'low', 75, 0, 4, 12);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (241, 'Sub - Fix', 30, 'completed', 'high', 100, 68, 4, 12, 240);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (242, 'Sub - Analyze', 24, 'in_progress', 'low', 75, 0, 4, 12, 240);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (243, 'Sub - Fix', 16, 'in_progress', 'low', 75, 0, 4, 12, 240);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (244, 'Sub - Fix', 6, 'in_progress', 'high', 50, 0, 4, 12, 240);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (245, 'Test module', 24, 'completed', 'high', 100, 93, 4, 12);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (246, 'Sub - Implement', 7, 'completed', 'low', 100, 96, 4, 12, 245);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (247, 'Sub - Deploy', 16, 'completed', 'urgent', 100, 62, 4, 12, 245);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (248, 'Refactor endpoint', 6, 'completed', 'urgent', 100, 79, 4, 12);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (249, 'Sub - Implement', 30, 'completed', 'low', 100, 61, 4, 12, 248);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (250, 'Sub - Refactor', 6, 'completed', 'urgent', 100, 53, 4, 12, 248);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (251, 'Refactor dashboard', 7, 'in_progress', 'high', 75, 0, 4, 12);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (252, 'Sub - Review', 6, 'in_progress', 'low', 75, 0, 4, 12, 251);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (253, 'Test dashboard', 6, 'completed', 'high', 100, 66, 4, 12);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (254, 'Sub - Refactor', 16, 'completed', 'urgent', 100, 91, 4, 12, 253);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (255, 'Document database', 30, 'completed', 'normal', 100, 92, 4, 12);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (256, 'Sub - Implement', 16, 'completed', 'high', 100, 57, 4, 12, 255);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (257, 'Sub - Deploy', 24, 'completed', 'urgent', 100, 89, 4, 12, 255);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (258, 'Sub - Analyze', 24, 'completed', 'high', 100, 76, 4, 12, 255);
                    

            INSERT INTO phases (id, project_id, title)
            VALUES (13, 4, 'Development');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (259, 'Fix pipeline', 30, 'in_progress', 'urgent', 59, 0, 4, 13);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (260, 'Sub - Test', 30, 'pending', 'high', 0, 0, 4, 13, 259);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (261, 'Sub - Design', 7, 'in_progress', 'urgent', 75, 0, 4, 13, 259);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (262, 'Sub - Test', 6, 'completed', 'urgent', 100, 61, 4, 13, 259);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (263, 'Document module', 6, 'in_progress', 'urgent', 84, 0, 4, 13);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (264, 'Sub - Review', 6, 'completed', 'urgent', 100, 89, 4, 13, 263);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (265, 'Sub - Optimize', 6, 'in_progress', 'high', 75, 0, 4, 13, 263);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (266, 'Sub - Fix', 7, 'in_progress', 'normal', 75, 0, 4, 13, 263);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (267, 'Review dashboard', 6, 'in_progress', 'high', 50, 0, 4, 13);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (268, 'Sub - Review', 6, 'completed', 'urgent', 100, 94, 4, 13, 267);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (269, 'Sub - Fix', 30, 'pending', 'normal', 0, 0, 4, 13, 267);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (270, 'Design API', 24, 'in_progress', 'normal', 50, 0, 4, 13);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (271, 'Sub - Refactor', 24, 'completed', 'normal', 100, 50, 4, 13, 270);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (272, 'Sub - Fix', 30, 'pending', 'high', 0, 0, 4, 13, 270);
                    

        INSERT INTO projects (id, title, staff_id, status, percentage, start_date, due_date)
        VALUES (5, 'Scalable Pipeline 5', 6, 'active', 66, '2022-07-29', '2023-06-26');
        

            INSERT INTO phases (id, project_id, title)
            VALUES (14, 5, 'Design');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (273, 'Analyze workflow', 16, 'completed', 'low', 100, 84, 5, 14);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (274, 'Sub - Design', 16, 'completed', 'low', 100, 83, 5, 14, 273);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (275, 'Sub - Review', 24, 'completed', 'urgent', 100, 60, 5, 14, 273);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (276, 'Implement pipeline', 30, 'in_progress', 'urgent', 57, 0, 5, 14);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (277, 'Sub - Implement', 30, 'in_progress', 'normal', 50, 0, 5, 14, 276);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (278, 'Sub - Deploy', 7, 'completed', 'high', 100, 74, 5, 14, 276);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (279, 'Sub - Fix', 7, 'in_progress', 'low', 50, 0, 5, 14, 276);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (280, 'Sub - Implement', 16, 'in_progress', 'low', 25, 0, 5, 14, 276);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (281, 'Fix database', 16, 'in_progress', 'urgent', 57, 0, 5, 14);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (282, 'Sub - Optimize', 30, 'completed', 'low', 100, 58, 5, 14, 281);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (283, 'Sub - Fix', 6, 'completed', 'urgent', 100, 71, 5, 14, 281);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (284, 'Sub - Review', 7, 'in_progress', 'normal', 25, 0, 5, 14, 281);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (285, 'Sub - Test', 7, 'pending', 'normal', 0, 0, 5, 14, 281);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (286, 'Analyze service', 16, 'completed', 'urgent', 100, 77, 5, 14);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (287, 'Sub - Optimize', 7, 'completed', 'normal', 100, 61, 5, 14, 286);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (288, 'Sub - Test', 16, 'completed', 'normal', 100, 65, 5, 14, 286);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (289, 'Document module', 16, 'in_progress', 'normal', 82, 0, 5, 14);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (290, 'Sub - Design', 6, 'completed', 'urgent', 100, 81, 5, 14, 289);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (291, 'Sub - Deploy', 24, 'completed', 'urgent', 100, 70, 5, 14, 289);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (292, 'Sub - Document', 30, 'in_progress', 'low', 25, 0, 5, 14, 289);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (293, 'Sub - Fix', 16, 'completed', 'low', 100, 93, 5, 14, 289);
                    

            INSERT INTO phases (id, project_id, title)
            VALUES (15, 5, 'Planning');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (294, 'Optimize module', 7, 'in_progress', 'low', 67, 0, 5, 15);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (295, 'Sub - Fix', 16, 'completed', 'low', 100, 70, 5, 15, 294);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (296, 'Sub - Analyze', 30, 'pending', 'low', 0, 0, 5, 15, 294);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (297, 'Sub - Document', 30, 'completed', 'normal', 100, 96, 5, 15, 294);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (298, 'Deploy endpoint', 16, 'in_progress', 'high', 67, 0, 5, 15);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (299, 'Sub - Optimize', 6, 'completed', 'normal', 100, 70, 5, 15, 298);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (300, 'Sub - Fix', 16, 'pending', 'urgent', 0, 0, 5, 15, 298);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (301, 'Sub - Refactor', 24, 'completed', 'low', 100, 54, 5, 15, 298);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (302, 'Optimize endpoint', 16, 'in_progress', 'high', 67, 0, 5, 15);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (303, 'Sub - Review', 16, 'pending', 'high', 0, 0, 5, 15, 302);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (304, 'Sub - Implement', 6, 'completed', 'urgent', 100, 56, 5, 15, 302);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (305, 'Sub - Implement', 6, 'completed', 'high', 100, 60, 5, 15, 302);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (306, 'Document workflow', 7, 'in_progress', 'low', 50, 0, 5, 15);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (307, 'Sub - Test', 16, 'in_progress', 'normal', 50, 0, 5, 15, 306);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (308, 'Sub - Design', 6, 'in_progress', 'low', 50, 0, 5, 15, 306);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (309, 'Sub - Implement', 6, 'in_progress', 'low', 50, 0, 5, 15, 306);
                    

            INSERT INTO phases (id, project_id, title)
            VALUES (16, 5, 'Deployment');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (310, 'Refactor database', 6, 'in_progress', 'high', 50, 0, 5, 16);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (311, 'Sub - Test', 30, 'completed', 'low', 100, 91, 5, 16, 310);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (312, 'Sub - Optimize', 30, 'pending', 'high', 0, 0, 5, 16, 310);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (313, 'Deploy service', 6, 'in_progress', 'urgent', 50, 0, 5, 16);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (314, 'Sub - Refactor', 7, 'in_progress', 'high', 50, 0, 5, 16, 313);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (315, 'Document database', 16, 'in_progress', 'normal', 34, 0, 5, 16);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (316, 'Sub - Refactor', 6, 'in_progress', 'normal', 25, 0, 5, 16, 315);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (317, 'Sub - Deploy', 6, 'in_progress', 'low', 25, 0, 5, 16, 315);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (318, 'Sub - Review', 6, 'in_progress', 'normal', 50, 0, 5, 16, 315);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (319, 'Implement module', 6, 'in_progress', 'urgent', 69, 0, 5, 16);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (320, 'Sub - Optimize', 16, 'in_progress', 'normal', 75, 0, 5, 16, 319);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (321, 'Sub - Test', 16, 'in_progress', 'high', 50, 0, 5, 16, 319);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (322, 'Sub - Review', 30, 'completed', 'high', 100, 52, 5, 16, 319);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (323, 'Sub - Analyze', 24, 'in_progress', 'urgent', 50, 0, 5, 16, 319);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (324, 'Test pipeline', 16, 'in_progress', 'urgent', 50, 0, 5, 16);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (325, 'Sub - Review', 6, 'pending', 'urgent', 0, 0, 5, 16, 324);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (326, 'Sub - Deploy', 7, 'in_progress', 'urgent', 50, 0, 5, 16, 324);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (327, 'Sub - Fix', 6, 'completed', 'high', 100, 58, 5, 16, 324);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (328, 'Analyze API', 24, 'in_progress', 'normal', 50, 0, 5, 16);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (329, 'Sub - Fix', 24, 'in_progress', 'high', 50, 0, 5, 16, 328);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (330, 'Deploy workflow', 30, 'in_progress', 'high', 63, 0, 5, 16);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (331, 'Sub - Fix', 7, 'in_progress', 'high', 25, 0, 5, 16, 330);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (332, 'Sub - Document', 24, 'completed', 'high', 100, 92, 5, 16, 330);
                    

            INSERT INTO phases (id, project_id, title)
            VALUES (17, 5, 'Design');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (333, 'Analyze endpoint', 24, 'in_progress', 'high', 13, 0, 5, 17);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (334, 'Sub - Review', 16, 'in_progress', 'low', 25, 0, 5, 17, 333);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (335, 'Sub - Review', 30, 'pending', 'high', 0, 0, 5, 17, 333);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (336, 'Analyze dashboard', 30, 'in_progress', 'low', 50, 0, 5, 17);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (337, 'Sub - Deploy', 7, 'in_progress', 'low', 50, 0, 5, 17, 336);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (338, 'Implement UI', 30, 'in_progress', 'low', 84, 0, 5, 17);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (339, 'Sub - Design', 6, 'in_progress', 'low', 75, 0, 5, 17, 338);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (340, 'Sub - Implement', 24, 'completed', 'normal', 100, 54, 5, 17, 338);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (341, 'Sub - Design', 16, 'in_progress', 'urgent', 75, 0, 5, 17, 338);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (342, 'Review database', 16, 'in_progress', 'low', 50, 0, 5, 17);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (343, 'Sub - Document', 16, 'in_progress', 'high', 50, 0, 5, 17, 342);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (344, 'Implement database', 6, 'completed', 'high', 100, 97, 5, 17);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (345, 'Sub - Test', 7, 'completed', 'low', 100, 71, 5, 17, 344);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (346, 'Design endpoint', 6, 'completed', 'normal', 100, 91, 5, 17);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (347, 'Sub - Fix', 16, 'completed', 'high', 100, 63, 5, 17, 346);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (348, 'Sub - Optimize', 24, 'completed', 'low', 100, 60, 5, 17, 346);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (349, 'Sub - Deploy', 24, 'completed', 'urgent', 100, 59, 5, 17, 346);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (350, 'Analyze API', 30, 'in_progress', 'low', 75, 0, 5, 17);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (351, 'Sub - Optimize', 24, 'completed', 'low', 100, 83, 5, 17, 350);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (352, 'Sub - Optimize', 7, 'in_progress', 'normal', 75, 0, 5, 17, 350);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (353, 'Sub - Fix', 16, 'completed', 'high', 100, 63, 5, 17, 350);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (354, 'Sub - Design', 16, 'in_progress', 'high', 25, 0, 5, 17, 350);
                    

        INSERT INTO projects (id, title, staff_id, status, percentage, start_date, due_date)
        VALUES (6, 'Smart Dashboard 6', 2, 'active', 70, '2024-03-29', '2025-03-07');
        

            INSERT INTO phases (id, project_id, title)
            VALUES (18, 6, 'Deployment');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (355, 'Fix dashboard', 2, 'in_progress', 'urgent', 25, 0, 6, 18);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (356, 'Sub - Refactor', 12, 'pending', 'urgent', 0, 0, 6, 18, 355);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (357, 'Sub - Implement', 15, 'pending', 'high', 0, 0, 6, 18, 355);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (358, 'Sub - Implement', 2, 'in_progress', 'high', 75, 0, 6, 18, 355);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (359, 'Design workflow', 12, 'in_progress', 'urgent', 67, 0, 6, 18);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (360, 'Sub - Review', 2, 'pending', 'urgent', 0, 0, 6, 18, 359);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (361, 'Sub - Analyze', 12, 'completed', 'urgent', 100, 79, 6, 18, 359);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (362, 'Sub - Review', 18, 'completed', 'urgent', 100, 75, 6, 18, 359);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (363, 'Analyze dashboard', 15, 'in_progress', 'low', 44, 0, 6, 18);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (364, 'Sub - Optimize', 17, 'completed', 'normal', 100, 59, 6, 18, 363);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (365, 'Sub - Analyze', 2, 'in_progress', 'normal', 25, 0, 6, 18, 363);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (366, 'Sub - Document', 17, 'in_progress', 'low', 25, 0, 6, 18, 363);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (367, 'Sub - Test', 18, 'in_progress', 'high', 25, 0, 6, 18, 363);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (368, 'Deploy pipeline', 12, 'in_progress', 'normal', 75, 0, 6, 18);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (369, 'Sub - Design', 17, 'completed', 'urgent', 100, 53, 6, 18, 368);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (370, 'Sub - Implement', 15, 'completed', 'high', 100, 66, 6, 18, 368);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (371, 'Sub - Deploy', 18, 'in_progress', 'normal', 25, 0, 6, 18, 368);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (372, 'Optimize module', 17, 'in_progress', 'normal', 92, 0, 6, 18);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (373, 'Sub - Fix', 12, 'in_progress', 'urgent', 75, 0, 6, 18, 372);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (374, 'Sub - Review', 18, 'completed', 'low', 100, 67, 6, 18, 372);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (375, 'Sub - Test', 17, 'completed', 'low', 100, 93, 6, 18, 372);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (376, 'Document service', 15, 'in_progress', 'urgent', 67, 0, 6, 18);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (377, 'Sub - Optimize', 2, 'pending', 'high', 0, 0, 6, 18, 376);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (378, 'Sub - Document', 18, 'completed', 'low', 100, 71, 6, 18, 376);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (379, 'Sub - Test', 18, 'completed', 'urgent', 100, 67, 6, 18, 376);
                    

            INSERT INTO phases (id, project_id, title)
            VALUES (19, 6, 'Development');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (380, 'Review API', 15, 'in_progress', 'normal', 92, 0, 6, 19);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (381, 'Sub - Document', 17, 'completed', 'normal', 100, 77, 6, 19, 380);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (382, 'Sub - Optimize', 2, 'completed', 'low', 100, 58, 6, 19, 380);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (383, 'Sub - Implement', 17, 'in_progress', 'urgent', 75, 0, 6, 19, 380);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (384, 'Document UI', 17, 'in_progress', 'urgent', 92, 0, 6, 19);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (385, 'Sub - Analyze', 2, 'completed', 'urgent', 100, 97, 6, 19, 384);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (386, 'Sub - Implement', 15, 'completed', 'low', 100, 98, 6, 19, 384);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (387, 'Sub - Analyze', 17, 'in_progress', 'high', 75, 0, 6, 19, 384);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (388, 'Fix endpoint', 12, 'in_progress', 'high', 75, 0, 6, 19);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (389, 'Sub - Test', 17, 'completed', 'normal', 100, 58, 6, 19, 388);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (390, 'Sub - Review', 2, 'completed', 'low', 100, 68, 6, 19, 388);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (391, 'Sub - Test', 15, 'completed', 'low', 100, 83, 6, 19, 388);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (392, 'Sub - Test', 18, 'pending', 'high', 0, 0, 6, 19, 388);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (393, 'Implement pipeline', 15, 'in_progress', 'low', 75, 0, 6, 19);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (394, 'Sub - Implement', 12, 'completed', 'high', 100, 98, 6, 19, 393);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (395, 'Sub - Deploy', 15, 'in_progress', 'high', 50, 0, 6, 19, 393);
                    

            INSERT INTO phases (id, project_id, title)
            VALUES (20, 6, 'Testing');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (396, 'Implement endpoint', 18, 'in_progress', 'high', 57, 0, 6, 20);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (397, 'Sub - Deploy', 18, 'pending', 'high', 0, 0, 6, 20, 396);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (398, 'Sub - Design', 18, 'completed', 'high', 100, 97, 6, 20, 396);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (399, 'Sub - Analyze', 18, 'in_progress', 'urgent', 25, 0, 6, 20, 396);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (400, 'Sub - Optimize', 15, 'completed', 'low', 100, 78, 6, 20, 396);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (401, 'Refactor workflow', 15, 'in_progress', 'high', 88, 0, 6, 20);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (402, 'Sub - Fix', 17, 'completed', 'high', 100, 59, 6, 20, 401);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (403, 'Sub - Document', 2, 'in_progress', 'low', 75, 0, 6, 20, 401);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (404, 'Fix dashboard', 18, 'in_progress', 'normal', 63, 0, 6, 20);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (405, 'Sub - Implement', 2, 'in_progress', 'high', 25, 0, 6, 20, 404);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (406, 'Sub - Review', 18, 'completed', 'high', 100, 61, 6, 20, 404);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (407, 'Analyze API', 15, 'completed', 'normal', 100, 81, 6, 20);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (408, 'Sub - Analyze', 18, 'completed', 'low', 100, 89, 6, 20, 407);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (409, 'Sub - Design', 12, 'completed', 'normal', 100, 98, 6, 20, 407);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (410, 'Sub - Design', 17, 'completed', 'low', 100, 57, 6, 20, 407);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (411, 'Design UI', 18, 'in_progress', 'low', 50, 0, 6, 20);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (412, 'Sub - Deploy', 2, 'completed', 'high', 100, 63, 6, 20, 411);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (413, 'Sub - Refactor', 2, 'pending', 'normal', 0, 0, 6, 20, 411);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (414, 'Deploy workflow', 15, 'in_progress', 'urgent', 75, 0, 6, 20);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (415, 'Sub - Implement', 2, 'in_progress', 'high', 75, 0, 6, 20, 414);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (416, 'Document endpoint', 18, 'pending', 'urgent', 0, 0, 6, 20);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (417, 'Sub - Fix', 12, 'pending', 'urgent', 0, 0, 6, 20, 416);
                    

        INSERT INTO projects (id, title, staff_id, status, percentage, start_date, due_date)
        VALUES (7, 'Cloud Dashboard 7', 6, 'active', 66, '2022-06-22', '2023-04-25');
        

            INSERT INTO phases (id, project_id, title)
            VALUES (21, 7, 'Design');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (418, 'Review service', 7, 'in_progress', 'normal', 75, 0, 7, 21);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (419, 'Sub - Design', 7, 'in_progress', 'high', 50, 0, 7, 21, 418);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (420, 'Sub - Document', 16, 'completed', 'normal', 100, 75, 7, 21, 418);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (421, 'Document service', 16, 'in_progress', 'urgent', 82, 0, 7, 21);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (422, 'Sub - Review', 30, 'completed', 'normal', 100, 96, 7, 21, 421);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (423, 'Sub - Analyze', 24, 'in_progress', 'low', 50, 0, 7, 21, 421);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (424, 'Sub - Test', 30, 'in_progress', 'high', 75, 0, 7, 21, 421);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (425, 'Sub - Fix', 6, 'completed', 'low', 100, 98, 7, 21, 421);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (426, 'Document service', 16, 'in_progress', 'low', 94, 0, 7, 21);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (427, 'Sub - Analyze', 16, 'completed', 'high', 100, 86, 7, 21, 426);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (428, 'Sub - Refactor', 16, 'completed', 'high', 100, 53, 7, 21, 426);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (429, 'Sub - Test', 16, 'completed', 'low', 100, 55, 7, 21, 426);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (430, 'Sub - Deploy', 24, 'in_progress', 'urgent', 75, 0, 7, 21, 426);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (431, 'Optimize workflow', 7, 'completed', 'high', 100, 71, 7, 21);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (432, 'Sub - Document', 24, 'completed', 'urgent', 100, 69, 7, 21, 431);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (433, 'Review pipeline', 16, 'in_progress', 'normal', 63, 0, 7, 21);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (434, 'Sub - Analyze', 7, 'in_progress', 'high', 25, 0, 7, 21, 433);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (435, 'Sub - Optimize', 24, 'in_progress', 'high', 50, 0, 7, 21, 433);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (436, 'Sub - Test', 16, 'in_progress', 'normal', 75, 0, 7, 21, 433);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (437, 'Sub - Design', 16, 'completed', 'low', 100, 82, 7, 21, 433);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (438, 'Design service', 6, 'completed', 'urgent', 100, 73, 7, 21);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (439, 'Sub - Analyze', 30, 'completed', 'low', 100, 87, 7, 21, 438);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (440, 'Sub - Analyze', 7, 'completed', 'urgent', 100, 79, 7, 21, 438);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (441, 'Sub - Review', 30, 'completed', 'high', 100, 66, 7, 21, 438);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (442, 'Document service', 7, 'in_progress', 'normal', 67, 0, 7, 21);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (443, 'Sub - Analyze', 24, 'in_progress', 'low', 75, 0, 7, 21, 442);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (444, 'Sub - Review', 16, 'in_progress', 'high', 75, 0, 7, 21, 442);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (445, 'Sub - Analyze', 24, 'in_progress', 'low', 50, 0, 7, 21, 442);
                    

            INSERT INTO phases (id, project_id, title)
            VALUES (22, 7, 'Testing');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (446, 'Implement module', 7, 'completed', 'normal', 100, 71, 7, 22);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (447, 'Sub - Review', 16, 'completed', 'high', 100, 87, 7, 22, 446);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (448, 'Sub - Refactor', 6, 'completed', 'low', 100, 86, 7, 22, 446);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (449, 'Review API', 6, 'in_progress', 'low', 13, 0, 7, 22);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (450, 'Sub - Implement', 7, 'pending', 'normal', 0, 0, 7, 22, 449);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (451, 'Sub - Optimize', 7, 'in_progress', 'high', 25, 0, 7, 22, 449);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (452, 'Deploy module', 6, 'completed', 'high', 100, 62, 7, 22);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (453, 'Sub - Refactor', 7, 'completed', 'normal', 100, 68, 7, 22, 452);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (454, 'Refactor service', 7, 'pending', 'urgent', 0, 0, 7, 22);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (455, 'Sub - Document', 24, 'pending', 'urgent', 0, 0, 7, 22, 454);
                    

            INSERT INTO phases (id, project_id, title)
            VALUES (23, 7, 'Testing');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (456, 'Fix API', 6, 'in_progress', 'high', 38, 0, 7, 23);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (457, 'Sub - Design', 7, 'in_progress', 'normal', 75, 0, 7, 23, 456);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (458, 'Sub - Optimize', 30, 'pending', 'high', 0, 0, 7, 23, 456);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (459, 'Deploy pipeline', 6, 'completed', 'normal', 100, 86, 7, 23);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (460, 'Sub - Implement', 16, 'completed', 'urgent', 100, 91, 7, 23, 459);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (461, 'Sub - Implement', 7, 'completed', 'low', 100, 94, 7, 23, 459);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (462, 'Sub - Analyze', 7, 'completed', 'normal', 100, 50, 7, 23, 459);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (463, 'Analyze workflow', 16, 'in_progress', 'urgent', 50, 0, 7, 23);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (464, 'Sub - Document', 30, 'completed', 'low', 100, 90, 7, 23, 463);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (465, 'Sub - Analyze', 7, 'pending', 'normal', 0, 0, 7, 23, 463);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (466, 'Test workflow', 16, 'in_progress', 'urgent', 50, 0, 7, 23);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (467, 'Sub - Deploy', 30, 'in_progress', 'normal', 75, 0, 7, 23, 466);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (468, 'Sub - Review', 16, 'in_progress', 'low', 25, 0, 7, 23, 466);
                    

        INSERT INTO projects (id, title, staff_id, status, percentage, start_date, due_date)
        VALUES (8, 'Cloud System 8', 6, 'active', 71, '2020-09-27', '2021-01-01');
        

            INSERT INTO phases (id, project_id, title)
            VALUES (24, 8, 'Testing');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (469, 'Test UI', 24, 'completed', 'low', 100, 62, 8, 24);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (470, 'Sub - Optimize', 16, 'completed', 'urgent', 100, 69, 8, 24, 469);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (471, 'Refactor pipeline', 6, 'in_progress', 'urgent', 50, 0, 8, 24);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (472, 'Sub - Optimize', 24, 'in_progress', 'normal', 50, 0, 8, 24, 471);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (473, 'Design endpoint', 6, 'in_progress', 'high', 88, 0, 8, 24);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (474, 'Sub - Refactor', 16, 'completed', 'normal', 100, 87, 8, 24, 473);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (475, 'Sub - Document', 6, 'in_progress', 'normal', 75, 0, 8, 24, 473);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (476, 'Analyze module', 24, 'in_progress', 'high', 75, 0, 8, 24);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (477, 'Sub - Review', 30, 'in_progress', 'urgent', 75, 0, 8, 24, 476);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (478, 'Test UI', 6, 'in_progress', 'normal', 69, 0, 8, 24);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (479, 'Sub - Fix', 16, 'completed', 'normal', 100, 79, 8, 24, 478);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (480, 'Sub - Refactor', 24, 'pending', 'urgent', 0, 0, 8, 24, 478);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (481, 'Sub - Refactor', 30, 'completed', 'high', 100, 79, 8, 24, 478);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (482, 'Sub - Optimize', 7, 'in_progress', 'normal', 75, 0, 8, 24, 478);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (483, 'Refactor workflow', 30, 'in_progress', 'low', 34, 0, 8, 24);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (484, 'Sub - Test', 24, 'in_progress', 'high', 25, 0, 8, 24, 483);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (485, 'Sub - Implement', 16, 'in_progress', 'urgent', 25, 0, 8, 24, 483);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (486, 'Sub - Implement', 24, 'in_progress', 'urgent', 50, 0, 8, 24, 483);
                    

            INSERT INTO phases (id, project_id, title)
            VALUES (25, 8, 'Design');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (487, 'Design workflow', 24, 'completed', 'normal', 100, 100, 8, 25);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (488, 'Sub - Fix', 24, 'completed', 'normal', 100, 96, 8, 25, 487);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (489, 'Design API', 24, 'completed', 'normal', 100, 65, 8, 25);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (490, 'Sub - Test', 6, 'completed', 'low', 100, 74, 8, 25, 489);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (491, 'Deploy module', 30, 'in_progress', 'high', 88, 0, 8, 25);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (492, 'Sub - Design', 7, 'in_progress', 'high', 75, 0, 8, 25, 491);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (493, 'Sub - Design', 7, 'completed', 'normal', 100, 95, 8, 25, 491);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (494, 'Implement workflow', 16, 'in_progress', 'normal', 75, 0, 8, 25);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (495, 'Sub - Implement', 16, 'in_progress', 'normal', 50, 0, 8, 25, 494);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (496, 'Sub - Refactor', 16, 'in_progress', 'high', 50, 0, 8, 25, 494);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (497, 'Sub - Analyze', 16, 'completed', 'high', 100, 88, 8, 25, 494);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (498, 'Sub - Review', 6, 'completed', 'urgent', 100, 80, 8, 25, 494);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (499, 'Deploy API', 30, 'in_progress', 'normal', 75, 0, 8, 25);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (500, 'Sub - Fix', 24, 'in_progress', 'low', 75, 0, 8, 25, 499);
                    

            INSERT INTO phases (id, project_id, title)
            VALUES (26, 8, 'Testing');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (501, 'Document workflow', 16, 'in_progress', 'low', 13, 0, 8, 26);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (502, 'Sub - Fix', 16, 'pending', 'normal', 0, 0, 8, 26, 501);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (503, 'Sub - Fix', 16, 'in_progress', 'high', 25, 0, 8, 26, 501);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (504, 'Refactor database', 30, 'in_progress', 'normal', 38, 0, 8, 26);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (505, 'Sub - Fix', 30, 'pending', 'normal', 0, 0, 8, 26, 504);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (506, 'Sub - Test', 6, 'in_progress', 'normal', 25, 0, 8, 26, 504);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (507, 'Sub - Test', 7, 'in_progress', 'normal', 25, 0, 8, 26, 504);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (508, 'Sub - Refactor', 16, 'completed', 'high', 100, 94, 8, 26, 504);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (509, 'Fix dashboard', 24, 'completed', 'normal', 100, 76, 8, 26);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (510, 'Sub - Optimize', 7, 'completed', 'normal', 100, 91, 8, 26, 509);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (511, 'Sub - Fix', 7, 'completed', 'high', 100, 55, 8, 26, 509);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (512, 'Refactor module', 24, 'in_progress', 'normal', 63, 0, 8, 26);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (513, 'Sub - Fix', 6, 'in_progress', 'urgent', 25, 0, 8, 26, 512);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (514, 'Sub - Design', 7, 'completed', 'low', 100, 52, 8, 26, 512);
                    

        INSERT INTO projects (id, title, staff_id, status, percentage, start_date, due_date)
        VALUES (9, 'Smart System 9', 5, 'active', 62, '2023-12-28', '2024-06-03');
        

            INSERT INTO phases (id, project_id, title)
            VALUES (27, 9, 'Planning');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (515, 'Document database', 9, 'in_progress', 'normal', 32, 0, 9, 27);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (516, 'Sub - Implement', 9, 'completed', 'urgent', 100, 70, 9, 27, 515);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (517, 'Sub - Document', 28, 'pending', 'normal', 0, 0, 9, 27, 515);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (518, 'Sub - Test', 9, 'in_progress', 'low', 25, 0, 9, 27, 515);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (519, 'Sub - Test', 28, 'pending', 'high', 0, 0, 9, 27, 515);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (520, 'Optimize database', 5, 'completed', 'urgent', 100, 71, 9, 27);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (521, 'Sub - Refactor', 28, 'completed', 'normal', 100, 52, 9, 27, 520);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (522, 'Refactor module', 28, 'completed', 'urgent', 100, 80, 9, 27);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (523, 'Sub - Review', 28, 'completed', 'normal', 100, 90, 9, 27, 522);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (524, 'Sub - Implement', 23, 'completed', 'normal', 100, 66, 9, 27, 522);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (525, 'Refactor UI', 5, 'in_progress', 'normal', 63, 0, 9, 27);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (526, 'Sub - Design', 28, 'in_progress', 'urgent', 50, 0, 9, 27, 525);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (527, 'Sub - Document', 23, 'pending', 'urgent', 0, 0, 9, 27, 525);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (528, 'Sub - Deploy', 9, 'completed', 'normal', 100, 68, 9, 27, 525);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (529, 'Sub - Review', 23, 'completed', 'urgent', 100, 88, 9, 27, 525);
                    

            INSERT INTO phases (id, project_id, title)
            VALUES (28, 9, 'Testing');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (530, 'Deploy endpoint', 23, 'in_progress', 'urgent', 38, 0, 9, 28);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (531, 'Sub - Review', 28, 'pending', 'urgent', 0, 0, 9, 28, 530);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (532, 'Sub - Optimize', 9, 'in_progress', 'normal', 75, 0, 9, 28, 530);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (533, 'Fix dashboard', 23, 'in_progress', 'normal', 50, 0, 9, 28);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (534, 'Sub - Design', 5, 'in_progress', 'high', 25, 0, 9, 28, 533);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (535, 'Sub - Design', 28, 'in_progress', 'normal', 75, 0, 9, 28, 533);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (536, 'Analyze workflow', 14, 'completed', 'high', 100, 77, 9, 28);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (537, 'Sub - Design', 23, 'completed', 'urgent', 100, 84, 9, 28, 536);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (538, 'Sub - Refactor', 5, 'completed', 'normal', 100, 62, 9, 28, 536);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (539, 'Refactor UI', 23, 'in_progress', 'urgent', 84, 0, 9, 28);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (540, 'Sub - Document', 14, 'completed', 'normal', 100, 91, 9, 28, 539);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (541, 'Sub - Test', 14, 'completed', 'urgent', 100, 90, 9, 28, 539);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (542, 'Sub - Design', 28, 'in_progress', 'normal', 50, 0, 9, 28, 539);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (543, 'Fix module', 14, 'in_progress', 'low', 50, 0, 9, 28);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (544, 'Sub - Fix', 23, 'in_progress', 'low', 50, 0, 9, 28, 543);
                    

            INSERT INTO phases (id, project_id, title)
            VALUES (29, 9, 'Planning');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (545, 'Design dashboard', 14, 'in_progress', 'normal', 25, 0, 9, 29);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (546, 'Sub - Optimize', 9, 'in_progress', 'low', 25, 0, 9, 29, 545);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (547, 'Analyze workflow', 23, 'in_progress', 'normal', 92, 0, 9, 29);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (548, 'Sub - Document', 28, 'completed', 'low', 100, 68, 9, 29, 547);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (549, 'Sub - Deploy', 5, 'completed', 'low', 100, 93, 9, 29, 547);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (550, 'Sub - Refactor', 23, 'in_progress', 'high', 75, 0, 9, 29, 547);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (551, 'Refactor module', 5, 'pending', 'low', 0, 0, 9, 29);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (552, 'Sub - Test', 5, 'pending', 'normal', 0, 0, 9, 29, 551);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (553, 'Fix pipeline', 28, 'in_progress', 'high', 75, 0, 9, 29);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (554, 'Sub - Fix', 9, 'in_progress', 'high', 75, 0, 9, 29, 553);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (555, 'Deploy endpoint', 23, 'in_progress', 'high', 75, 0, 9, 29);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (556, 'Sub - Document', 9, 'completed', 'normal', 100, 65, 9, 29, 555);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (557, 'Sub - Design', 28, 'in_progress', 'high', 50, 0, 9, 29, 555);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (558, 'Sub - Test', 23, 'in_progress', 'normal', 75, 0, 9, 29, 555);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (559, 'Document database', 23, 'pending', 'normal', 0, 0, 9, 29);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (560, 'Sub - Design', 28, 'pending', 'normal', 0, 0, 9, 29, 559);
                    

        INSERT INTO projects (id, title, staff_id, status, percentage, start_date, due_date)
        VALUES (10, 'Smart System 10', 5, 'active', 68, '2022-11-12', '2023-04-12');
        

            INSERT INTO phases (id, project_id, title)
            VALUES (30, 10, 'Deployment');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (561, 'Design module', 9, 'in_progress', 'low', 84, 0, 10, 30);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (562, 'Sub - Analyze', 28, 'completed', 'low', 100, 89, 10, 30, 561);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (563, 'Sub - Review', 23, 'completed', 'normal', 100, 73, 10, 30, 561);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (564, 'Sub - Fix', 14, 'in_progress', 'urgent', 50, 0, 10, 30, 561);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (565, 'Optimize workflow', 5, 'in_progress', 'high', 34, 0, 10, 30);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (566, 'Sub - Test', 9, 'in_progress', 'low', 50, 0, 10, 30, 565);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (567, 'Sub - Optimize', 23, 'in_progress', 'high', 50, 0, 10, 30, 565);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (568, 'Sub - Analyze', 28, 'pending', 'low', 0, 0, 10, 30, 565);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (569, 'Design UI', 14, 'completed', 'urgent', 100, 97, 10, 30);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (570, 'Sub - Review', 5, 'completed', 'high', 100, 99, 10, 30, 569);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (571, 'Optimize UI', 14, 'in_progress', 'normal', 50, 0, 10, 30);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (572, 'Sub - Analyze', 14, 'pending', 'low', 0, 0, 10, 30, 571);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (573, 'Sub - Deploy', 28, 'in_progress', 'normal', 50, 0, 10, 30, 571);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (574, 'Sub - Test', 5, 'completed', 'high', 100, 53, 10, 30, 571);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (575, 'Optimize endpoint', 5, 'in_progress', 'low', 50, 0, 10, 30);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (576, 'Sub - Fix', 28, 'completed', 'urgent', 100, 63, 10, 30, 575);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (577, 'Sub - Implement', 9, 'pending', 'urgent', 0, 0, 10, 30, 575);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (578, 'Implement workflow', 9, 'in_progress', 'high', 63, 0, 10, 30);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (579, 'Sub - Analyze', 5, 'completed', 'high', 100, 89, 10, 30, 578);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (580, 'Sub - Document', 14, 'in_progress', 'low', 25, 0, 10, 30, 578);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (581, 'Review database', 23, 'in_progress', 'high', 38, 0, 10, 30);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (582, 'Sub - Optimize', 5, 'in_progress', 'high', 25, 0, 10, 30, 581);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (583, 'Sub - Analyze', 14, 'in_progress', 'urgent', 25, 0, 10, 30, 581);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (584, 'Sub - Refactor', 9, 'completed', 'low', 100, 54, 10, 30, 581);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (585, 'Sub - Analyze', 28, 'pending', 'low', 0, 0, 10, 30, 581);
                    

            INSERT INTO phases (id, project_id, title)
            VALUES (31, 10, 'Development');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (586, 'Refactor dashboard', 23, 'in_progress', 'urgent', 50, 0, 10, 31);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (587, 'Sub - Test', 9, 'completed', 'low', 100, 50, 10, 31, 586);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (588, 'Sub - Implement', 9, 'pending', 'low', 0, 0, 10, 31, 586);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (589, 'Sub - Deploy', 5, 'in_progress', 'normal', 50, 0, 10, 31, 586);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (590, 'Analyze workflow', 23, 'completed', 'normal', 100, 72, 10, 31);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (591, 'Sub - Document', 28, 'completed', 'low', 100, 54, 10, 31, 590);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (592, 'Design dashboard', 9, 'completed', 'high', 100, 90, 10, 31);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (593, 'Sub - Review', 28, 'completed', 'urgent', 100, 57, 10, 31, 592);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (594, 'Sub - Implement', 28, 'completed', 'low', 100, 54, 10, 31, 592);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (595, 'Deploy service', 5, 'in_progress', 'high', 75, 0, 10, 31);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (596, 'Sub - Deploy', 9, 'in_progress', 'low', 50, 0, 10, 31, 595);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (597, 'Sub - Refactor', 23, 'completed', 'low', 100, 76, 10, 31, 595);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (598, 'Fix dashboard', 28, 'completed', 'normal', 100, 71, 10, 31);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (599, 'Sub - Implement', 28, 'completed', 'low', 100, 54, 10, 31, 598);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (600, 'Sub - Fix', 23, 'completed', 'urgent', 100, 88, 10, 31, 598);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (601, 'Implement module', 14, 'in_progress', 'low', 50, 0, 10, 31);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (602, 'Sub - Implement', 23, 'in_progress', 'normal', 50, 0, 10, 31, 601);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (603, 'Analyze module', 14, 'completed', 'normal', 100, 62, 10, 31);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (604, 'Sub - Analyze', 28, 'completed', 'low', 100, 67, 10, 31, 603);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (605, 'Sub - Optimize', 14, 'completed', 'high', 100, 51, 10, 31, 603);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (606, 'Sub - Design', 14, 'completed', 'normal', 100, 53, 10, 31, 603);
                    

            INSERT INTO phases (id, project_id, title)
            VALUES (32, 10, 'Development');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (607, 'Fix API', 9, 'in_progress', 'urgent', 44, 0, 10, 32);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (608, 'Sub - Refactor', 28, 'pending', 'normal', 0, 0, 10, 32, 607);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (609, 'Sub - Review', 14, 'in_progress', 'normal', 25, 0, 10, 32, 607);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (610, 'Sub - Analyze', 9, 'in_progress', 'normal', 50, 0, 10, 32, 607);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (611, 'Sub - Optimize', 23, 'completed', 'normal', 100, 86, 10, 32, 607);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (612, 'Fix workflow', 23, 'in_progress', 'high', 38, 0, 10, 32);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (613, 'Sub - Refactor', 23, 'in_progress', 'urgent', 75, 0, 10, 32, 612);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (614, 'Sub - Optimize', 9, 'pending', 'urgent', 0, 0, 10, 32, 612);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (615, 'Optimize API', 14, 'completed', 'normal', 100, 94, 10, 32);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (616, 'Sub - Deploy', 28, 'completed', 'urgent', 100, 57, 10, 32, 615);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (617, 'Review API', 28, 'in_progress', 'normal', 38, 0, 10, 32);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (618, 'Sub - Analyze', 5, 'in_progress', 'normal', 75, 0, 10, 32, 617);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (619, 'Sub - Fix', 14, 'pending', 'low', 0, 0, 10, 32, 617);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (620, 'Sub - Document', 9, 'in_progress', 'high', 75, 0, 10, 32, 617);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (621, 'Sub - Implement', 9, 'pending', 'low', 0, 0, 10, 32, 617);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (622, 'Test database', 28, 'in_progress', 'low', 92, 0, 10, 32);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (623, 'Sub - Refactor', 14, 'in_progress', 'high', 75, 0, 10, 32, 622);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (624, 'Sub - Analyze', 14, 'completed', 'urgent', 100, 73, 10, 32, 622);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (625, 'Sub - Design', 5, 'completed', 'high', 100, 69, 10, 32, 622);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (626, 'Fix endpoint', 9, 'completed', 'high', 100, 83, 10, 32);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (627, 'Sub - Review', 14, 'completed', 'high', 100, 94, 10, 32, 626);
                    

            INSERT INTO phases (id, project_id, title)
            VALUES (33, 10, 'Design');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (628, 'Refactor pipeline', 14, 'in_progress', 'low', 67, 0, 10, 33);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (629, 'Sub - Implement', 23, 'in_progress', 'normal', 50, 0, 10, 33, 628);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (630, 'Sub - Document', 23, 'completed', 'low', 100, 64, 10, 33, 628);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (631, 'Sub - Design', 5, 'in_progress', 'urgent', 50, 0, 10, 33, 628);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (632, 'Implement dashboard', 9, 'in_progress', 'high', 38, 0, 10, 33);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (633, 'Sub - Optimize', 14, 'in_progress', 'normal', 75, 0, 10, 33, 632);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (634, 'Sub - Analyze', 28, 'pending', 'normal', 0, 0, 10, 33, 632);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (635, 'Optimize endpoint', 5, 'completed', 'high', 100, 71, 10, 33);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (636, 'Sub - Fix', 28, 'completed', 'normal', 100, 70, 10, 33, 635);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (637, 'Sub - Refactor', 23, 'completed', 'urgent', 100, 64, 10, 33, 635);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (638, 'Sub - Fix', 23, 'completed', 'low', 100, 72, 10, 33, 635);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (639, 'Refactor workflow', 5, 'in_progress', 'low', 44, 0, 10, 33);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (640, 'Sub - Refactor', 23, 'pending', 'high', 0, 0, 10, 33, 639);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (641, 'Sub - Optimize', 5, 'pending', 'urgent', 0, 0, 10, 33, 639);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (642, 'Sub - Fix', 23, 'completed', 'normal', 100, 84, 10, 33, 639);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (643, 'Sub - Fix', 14, 'in_progress', 'low', 75, 0, 10, 33, 639);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (644, 'Optimize module', 9, 'completed', 'low', 100, 69, 10, 33);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (645, 'Sub - Review', 23, 'completed', 'low', 100, 63, 10, 33, 644);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (646, 'Sub - Document', 28, 'completed', 'normal', 100, 94, 10, 33, 644);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (647, 'Optimize database', 5, 'pending', 'high', 0, 0, 10, 33);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (648, 'Sub - Document', 9, 'pending', 'urgent', 0, 0, 10, 33, 647);
                    

        INSERT INTO projects (id, title, staff_id, status, percentage, start_date, due_date)
        VALUES (11, 'Cloud Dashboard 11', 2, 'active', 62, '2023-04-17', '2023-09-22');
        

            INSERT INTO phases (id, project_id, title)
            VALUES (34, 11, 'Design');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (649, 'Refactor API', 15, 'in_progress', 'high', 50, 0, 11, 34);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (650, 'Sub - Optimize', 2, 'in_progress', 'low', 50, 0, 11, 34, 649);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (651, 'Document dashboard', 12, 'in_progress', 'normal', 63, 0, 11, 34);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (652, 'Sub - Review', 15, 'completed', 'high', 100, 95, 11, 34, 651);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (653, 'Sub - Refactor', 12, 'in_progress', 'high', 25, 0, 11, 34, 651);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (654, 'Design database', 17, 'in_progress', 'normal', 88, 0, 11, 34);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (655, 'Sub - Refactor', 2, 'in_progress', 'low', 75, 0, 11, 34, 654);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (656, 'Sub - Fix', 15, 'completed', 'low', 100, 94, 11, 34, 654);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (657, 'Refactor pipeline', 2, 'in_progress', 'urgent', 57, 0, 11, 34);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (658, 'Sub - Review', 2, 'completed', 'normal', 100, 73, 11, 34, 657);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (659, 'Sub - Deploy', 18, 'completed', 'low', 100, 60, 11, 34, 657);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (660, 'Sub - Test', 12, 'in_progress', 'low', 25, 0, 11, 34, 657);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (661, 'Sub - Analyze', 2, 'pending', 'high', 0, 0, 11, 34, 657);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (662, 'Review workflow', 18, 'in_progress', 'low', 17, 0, 11, 34);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (663, 'Sub - Review', 12, 'pending', 'high', 0, 0, 11, 34, 662);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (664, 'Sub - Document', 12, 'pending', 'low', 0, 0, 11, 34, 662);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (665, 'Sub - Review', 2, 'in_progress', 'high', 50, 0, 11, 34, 662);
                    

            INSERT INTO phases (id, project_id, title)
            VALUES (35, 11, 'Deployment');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (666, 'Refactor endpoint', 12, 'in_progress', 'normal', 88, 0, 11, 35);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (667, 'Sub - Test', 2, 'completed', 'normal', 100, 81, 11, 35, 666);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (668, 'Sub - Fix', 18, 'completed', 'normal', 100, 59, 11, 35, 666);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (669, 'Sub - Analyze', 17, 'completed', 'high', 100, 61, 11, 35, 666);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (670, 'Sub - Refactor', 12, 'in_progress', 'high', 50, 0, 11, 35, 666);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (671, 'Review module', 12, 'pending', 'urgent', 0, 0, 11, 35);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (672, 'Sub - Test', 18, 'pending', 'high', 0, 0, 11, 35, 671);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (673, 'Design API', 17, 'in_progress', 'normal', 17, 0, 11, 35);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (674, 'Sub - Optimize', 17, 'in_progress', 'low', 50, 0, 11, 35, 673);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (675, 'Sub - Fix', 12, 'pending', 'high', 0, 0, 11, 35, 673);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (676, 'Sub - Deploy', 15, 'pending', 'low', 0, 0, 11, 35, 673);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (677, 'Optimize database', 12, 'in_progress', 'urgent', 25, 0, 11, 35);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (678, 'Sub - Deploy', 12, 'in_progress', 'low', 25, 0, 11, 35, 677);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (679, 'Fix service', 12, 'completed', 'low', 100, 99, 11, 35);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (680, 'Sub - Implement', 17, 'completed', 'high', 100, 50, 11, 35, 679);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (681, 'Document API', 12, 'completed', 'high', 100, 60, 11, 35);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (682, 'Sub - Document', 18, 'completed', 'high', 100, 54, 11, 35, 681);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (683, 'Design database', 18, 'in_progress', 'urgent', 88, 0, 11, 35);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (684, 'Sub - Design', 15, 'in_progress', 'low', 75, 0, 11, 35, 683);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (685, 'Sub - Review', 12, 'completed', 'low', 100, 68, 11, 35, 683);
                    

            INSERT INTO phases (id, project_id, title)
            VALUES (36, 11, 'Design');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (686, 'Fix dashboard', 15, 'in_progress', 'normal', 50, 0, 11, 36);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (687, 'Sub - Analyze', 15, 'pending', 'normal', 0, 0, 11, 36, 686);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (688, 'Sub - Test', 2, 'completed', 'urgent', 100, 58, 11, 36, 686);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (689, 'Design UI', 12, 'completed', 'normal', 100, 82, 11, 36);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (690, 'Sub - Analyze', 2, 'completed', 'low', 100, 75, 11, 36, 689);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (691, 'Sub - Refactor', 15, 'completed', 'normal', 100, 61, 11, 36, 689);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (692, 'Sub - Refactor', 17, 'completed', 'low', 100, 66, 11, 36, 689);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (693, 'Analyze UI', 12, 'in_progress', 'urgent', 25, 0, 11, 36);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (694, 'Sub - Test', 12, 'pending', 'normal', 0, 0, 11, 36, 693);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (695, 'Sub - Deploy', 17, 'in_progress', 'high', 50, 0, 11, 36, 693);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (696, 'Optimize UI', 18, 'in_progress', 'low', 50, 0, 11, 36);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (697, 'Sub - Fix', 12, 'in_progress', 'high', 50, 0, 11, 36, 696);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (698, 'Review API', 17, 'in_progress', 'normal', 88, 0, 11, 36);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (699, 'Sub - Review', 17, 'in_progress', 'high', 75, 0, 11, 36, 698);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (700, 'Sub - Design', 2, 'completed', 'urgent', 100, 95, 11, 36, 698);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (701, 'Sub - Design', 17, 'completed', 'low', 100, 62, 11, 36, 698);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (702, 'Sub - Implement', 17, 'in_progress', 'urgent', 75, 0, 11, 36, 698);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (703, 'Design dashboard', 18, 'completed', 'high', 100, 68, 11, 36);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (704, 'Sub - Test', 17, 'completed', 'high', 100, 61, 11, 36, 703);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (705, 'Sub - Fix', 12, 'completed', 'urgent', 100, 64, 11, 36, 703);
                    

        INSERT INTO projects (id, title, staff_id, status, percentage, start_date, due_date)
        VALUES (12, 'Realtime System 12', 4, 'active', 67, '2022-11-12', '2023-10-03');
        

            INSERT INTO phases (id, project_id, title)
            VALUES (37, 12, 'Planning');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (706, 'Deploy module', 26, 'in_progress', 'urgent', 25, 0, 12, 37);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (707, 'Sub - Implement', 21, 'in_progress', 'low', 25, 0, 12, 37, 706);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (708, 'Review module', 26, 'in_progress', 'high', 75, 0, 12, 37);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (709, 'Sub - Design', 19, 'in_progress', 'high', 75, 0, 12, 37, 708);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (710, 'Sub - Implement', 4, 'in_progress', 'low', 75, 0, 12, 37, 708);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (711, 'Design endpoint', 13, 'in_progress', 'normal', 88, 0, 12, 37);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (712, 'Sub - Refactor', 26, 'completed', 'urgent', 100, 58, 12, 37, 711);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (713, 'Sub - Refactor', 20, 'completed', 'high', 100, 95, 12, 37, 711);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (714, 'Sub - Refactor', 13, 'completed', 'high', 100, 57, 12, 37, 711);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (715, 'Sub - Review', 13, 'in_progress', 'low', 50, 0, 12, 37, 711);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (716, 'Optimize module', 13, 'in_progress', 'normal', 59, 0, 12, 37);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (717, 'Sub - Refactor', 20, 'completed', 'low', 100, 70, 12, 37, 716);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (718, 'Sub - Deploy', 27, 'in_progress', 'urgent', 25, 0, 12, 37, 716);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (719, 'Sub - Test', 19, 'in_progress', 'normal', 50, 0, 12, 37, 716);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (720, 'Review dashboard', 27, 'in_progress', 'high', 75, 0, 12, 37);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (721, 'Sub - Design', 27, 'in_progress', 'urgent', 25, 0, 12, 37, 720);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (722, 'Sub - Refactor', 27, 'completed', 'normal', 100, 56, 12, 37, 720);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (723, 'Sub - Refactor', 26, 'completed', 'urgent', 100, 99, 12, 37, 720);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (724, 'Fix module', 19, 'in_progress', 'urgent', 88, 0, 12, 37);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (725, 'Sub - Analyze', 27, 'completed', 'high', 100, 75, 12, 37, 724);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (726, 'Sub - Refactor', 20, 'in_progress', 'urgent', 75, 0, 12, 37, 724);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (727, 'Refactor UI', 4, 'pending', 'normal', 0, 0, 12, 37);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (728, 'Sub - Review', 21, 'pending', 'normal', 0, 0, 12, 37, 727);
                    

            INSERT INTO phases (id, project_id, title)
            VALUES (38, 12, 'Testing');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (729, 'Design database', 20, 'in_progress', 'low', 82, 0, 12, 38);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (730, 'Sub - Review', 20, 'completed', 'normal', 100, 77, 12, 38, 729);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (731, 'Sub - Fix', 4, 'in_progress', 'high', 75, 0, 12, 38, 729);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (732, 'Sub - Fix', 13, 'completed', 'normal', 100, 91, 12, 38, 729);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (733, 'Sub - Document', 13, 'in_progress', 'urgent', 50, 0, 12, 38, 729);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (734, 'Design endpoint', 19, 'completed', 'normal', 100, 87, 12, 38);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (735, 'Sub - Review', 21, 'completed', 'urgent', 100, 51, 12, 38, 734);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (736, 'Sub - Fix', 4, 'completed', 'normal', 100, 70, 12, 38, 734);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (737, 'Test endpoint', 26, 'in_progress', 'low', 63, 0, 12, 38);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (738, 'Sub - Analyze', 26, 'in_progress', 'high', 25, 0, 12, 38, 737);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (739, 'Sub - Deploy', 27, 'completed', 'normal', 100, 54, 12, 38, 737);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (740, 'Test UI', 19, 'in_progress', 'low', 50, 0, 12, 38);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (741, 'Sub - Analyze', 26, 'in_progress', 'normal', 75, 0, 12, 38, 740);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (742, 'Sub - Implement', 20, 'in_progress', 'low', 25, 0, 12, 38, 740);
                    

        INSERT INTO projects (id, title, staff_id, status, percentage, start_date, due_date)
        VALUES (13, 'Cloud Dashboard 13', 6, 'active', 63, '2025-10-12', '2026-10-10');
        

            INSERT INTO phases (id, project_id, title)
            VALUES (39, 13, 'Planning');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (743, 'Fix endpoint', 24, 'in_progress', 'normal', 32, 0, 13, 39);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (744, 'Sub - Optimize', 16, 'completed', 'urgent', 100, 68, 13, 39, 743);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (745, 'Sub - Design', 16, 'in_progress', 'high', 25, 0, 13, 39, 743);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (746, 'Sub - Test', 24, 'pending', 'normal', 0, 0, 13, 39, 743);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (747, 'Sub - Fix', 7, 'pending', 'low', 0, 0, 13, 39, 743);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (748, 'Refactor UI', 7, 'in_progress', 'high', 50, 0, 13, 39);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (749, 'Sub - Design', 16, 'pending', 'high', 0, 0, 13, 39, 748);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (750, 'Sub - Refactor', 7, 'in_progress', 'low', 75, 0, 13, 39, 748);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (751, 'Sub - Optimize', 30, 'in_progress', 'low', 25, 0, 13, 39, 748);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (752, 'Sub - Design', 6, 'completed', 'normal', 100, 95, 13, 39, 748);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (753, 'Implement UI', 30, 'in_progress', 'normal', 75, 0, 13, 39);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (754, 'Sub - Test', 30, 'completed', 'normal', 100, 66, 13, 39, 753);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (755, 'Sub - Implement', 24, 'completed', 'urgent', 100, 86, 13, 39, 753);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (756, 'Sub - Review', 6, 'in_progress', 'low', 50, 0, 13, 39, 753);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (757, 'Sub - Document', 30, 'in_progress', 'high', 50, 0, 13, 39, 753);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (758, 'Refactor dashboard', 7, 'in_progress', 'urgent', 75, 0, 13, 39);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (759, 'Sub - Implement', 24, 'completed', 'normal', 100, 68, 13, 39, 758);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (760, 'Sub - Review', 16, 'in_progress', 'normal', 50, 0, 13, 39, 758);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (761, 'Implement UI', 7, 'in_progress', 'low', 69, 0, 13, 39);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (762, 'Sub - Implement', 24, 'in_progress', 'urgent', 50, 0, 13, 39, 761);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (763, 'Sub - Analyze', 30, 'completed', 'low', 100, 95, 13, 39, 761);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (764, 'Sub - Optimize', 7, 'in_progress', 'normal', 75, 0, 13, 39, 761);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (765, 'Sub - Design', 30, 'in_progress', 'urgent', 50, 0, 13, 39, 761);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (766, 'Analyze API', 6, 'in_progress', 'normal', 25, 0, 13, 39);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (767, 'Sub - Refactor', 6, 'in_progress', 'low', 50, 0, 13, 39, 766);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (768, 'Sub - Document', 7, 'pending', 'low', 0, 0, 13, 39, 766);
                    

            INSERT INTO phases (id, project_id, title)
            VALUES (40, 13, 'Design');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (769, 'Document database', 30, 'completed', 'urgent', 100, 69, 13, 40);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (770, 'Sub - Test', 24, 'completed', 'urgent', 100, 66, 13, 40, 769);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (771, 'Implement pipeline', 16, 'in_progress', 'high', 75, 0, 13, 40);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (772, 'Sub - Analyze', 30, 'completed', 'high', 100, 52, 13, 40, 771);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (773, 'Sub - Analyze', 16, 'in_progress', 'normal', 50, 0, 13, 40, 771);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (774, 'Design module', 6, 'in_progress', 'normal', 57, 0, 13, 40);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (775, 'Sub - Fix', 7, 'in_progress', 'normal', 25, 0, 13, 40, 774);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (776, 'Sub - Design', 7, 'pending', 'urgent', 0, 0, 13, 40, 774);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (777, 'Sub - Deploy', 24, 'completed', 'high', 100, 57, 13, 40, 774);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (778, 'Sub - Design', 16, 'completed', 'high', 100, 51, 13, 40, 774);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (779, 'Refactor service', 6, 'in_progress', 'urgent', 50, 0, 13, 40);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (780, 'Sub - Refactor', 24, 'in_progress', 'urgent', 50, 0, 13, 40, 779);
                    

        INSERT INTO projects (id, title, staff_id, status, percentage, start_date, due_date)
        VALUES (14, 'Scalable Pipeline 14', 6, 'active', 72, '2021-03-05', '2022-02-09');
        

            INSERT INTO phases (id, project_id, title)
            VALUES (41, 14, 'Testing');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (781, 'Analyze database', 6, 'in_progress', 'normal', 34, 0, 14, 41);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (782, 'Sub - Deploy', 7, 'in_progress', 'normal', 25, 0, 14, 41, 781);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (783, 'Sub - Document', 7, 'pending', 'normal', 0, 0, 14, 41, 781);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (784, 'Sub - Document', 6, 'in_progress', 'high', 75, 0, 14, 41, 781);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (785, 'Refactor service', 6, 'in_progress', 'high', 50, 0, 14, 41);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (786, 'Sub - Design', 24, 'pending', 'normal', 0, 0, 14, 41, 785);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (787, 'Sub - Design', 24, 'completed', 'low', 100, 100, 14, 41, 785);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (788, 'Analyze UI', 6, 'in_progress', 'normal', 92, 0, 14, 41);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (789, 'Sub - Fix', 16, 'completed', 'high', 100, 75, 14, 41, 788);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (790, 'Sub - Design', 7, 'completed', 'normal', 100, 93, 14, 41, 788);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (791, 'Sub - Review', 7, 'in_progress', 'urgent', 75, 0, 14, 41, 788);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (792, 'Test module', 30, 'in_progress', 'low', 42, 0, 14, 41);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (793, 'Sub - Analyze', 16, 'pending', 'low', 0, 0, 14, 41, 792);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (794, 'Sub - Review', 30, 'completed', 'low', 100, 72, 14, 41, 792);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (795, 'Sub - Document', 16, 'in_progress', 'normal', 25, 0, 14, 41, 792);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (796, 'Document UI', 16, 'in_progress', 'urgent', 75, 0, 14, 41);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (797, 'Sub - Test', 30, 'in_progress', 'low', 25, 0, 14, 41, 796);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (798, 'Sub - Design', 30, 'completed', 'high', 100, 76, 14, 41, 796);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (799, 'Sub - Deploy', 24, 'completed', 'normal', 100, 99, 14, 41, 796);
                    

            INSERT INTO phases (id, project_id, title)
            VALUES (42, 14, 'Planning');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (800, 'Document pipeline', 30, 'in_progress', 'high', 82, 0, 14, 42);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (801, 'Sub - Document', 16, 'completed', 'high', 100, 61, 14, 42, 800);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (802, 'Sub - Test', 7, 'in_progress', 'high', 75, 0, 14, 42, 800);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (803, 'Sub - Analyze', 6, 'in_progress', 'normal', 50, 0, 14, 42, 800);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (804, 'Sub - Review', 7, 'completed', 'normal', 100, 96, 14, 42, 800);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (805, 'Refactor endpoint', 24, 'in_progress', 'high', 94, 0, 14, 42);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (806, 'Sub - Design', 6, 'in_progress', 'normal', 75, 0, 14, 42, 805);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (807, 'Sub - Optimize', 16, 'completed', 'urgent', 100, 100, 14, 42, 805);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (808, 'Sub - Refactor', 7, 'completed', 'normal', 100, 99, 14, 42, 805);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (809, 'Sub - Deploy', 24, 'completed', 'normal', 100, 72, 14, 42, 805);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (810, 'Review UI', 7, 'in_progress', 'low', 57, 0, 14, 42);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (811, 'Sub - Design', 6, 'completed', 'high', 100, 64, 14, 42, 810);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (812, 'Sub - Deploy', 16, 'pending', 'low', 0, 0, 14, 42, 810);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (813, 'Sub - Deploy', 7, 'completed', 'normal', 100, 94, 14, 42, 810);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (814, 'Sub - Design', 6, 'in_progress', 'urgent', 25, 0, 14, 42, 810);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (815, 'Implement API', 6, 'completed', 'high', 100, 75, 14, 42);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (816, 'Sub - Fix', 7, 'completed', 'high', 100, 63, 14, 42, 815);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (817, 'Sub - Test', 24, 'completed', 'high', 100, 82, 14, 42, 815);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (818, 'Sub - Document', 6, 'completed', 'normal', 100, 51, 14, 42, 815);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (819, 'Sub - Design', 30, 'completed', 'urgent', 100, 68, 14, 42, 815);
                    

        INSERT INTO projects (id, title, staff_id, status, percentage, start_date, due_date)
        VALUES (15, 'Realtime Pipeline 15', 2, 'active', 72, '2023-01-29', '2024-01-17');
        

            INSERT INTO phases (id, project_id, title)
            VALUES (43, 15, 'Development');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (820, 'Design endpoint', 12, 'in_progress', 'high', 42, 0, 15, 43);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (821, 'Sub - Refactor', 12, 'completed', 'high', 100, 64, 15, 43, 820);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (822, 'Sub - Deploy', 12, 'in_progress', 'low', 25, 0, 15, 43, 820);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (823, 'Sub - Review', 18, 'pending', 'normal', 0, 0, 15, 43, 820);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (824, 'Refactor database', 12, 'completed', 'high', 100, 68, 15, 43);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (825, 'Sub - Deploy', 17, 'completed', 'urgent', 100, 98, 15, 43, 824);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (826, 'Fix UI', 17, 'in_progress', 'low', 88, 0, 15, 43);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (827, 'Sub - Deploy', 18, 'completed', 'high', 100, 52, 15, 43, 826);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (828, 'Sub - Optimize', 15, 'in_progress', 'low', 50, 0, 15, 43, 826);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (829, 'Sub - Refactor', 15, 'completed', 'normal', 100, 73, 15, 43, 826);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (830, 'Sub - Deploy', 17, 'completed', 'urgent', 100, 59, 15, 43, 826);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (831, 'Deploy service', 12, 'in_progress', 'high', 50, 0, 15, 43);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (832, 'Sub - Optimize', 2, 'in_progress', 'urgent', 50, 0, 15, 43, 831);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (833, 'Sub - Review', 12, 'pending', 'normal', 0, 0, 15, 43, 831);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (834, 'Sub - Fix', 17, 'completed', 'urgent', 100, 84, 15, 43, 831);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (835, 'Analyze workflow', 12, 'pending', 'high', 0, 0, 15, 43);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (836, 'Sub - Fix', 12, 'pending', 'low', 0, 0, 15, 43, 835);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (837, 'Analyze dashboard', 18, 'in_progress', 'urgent', 94, 0, 15, 43);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (838, 'Sub - Analyze', 15, 'completed', 'high', 100, 85, 15, 43, 837);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (839, 'Sub - Document', 17, 'in_progress', 'low', 75, 0, 15, 43, 837);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (840, 'Sub - Refactor', 17, 'completed', 'low', 100, 99, 15, 43, 837);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (841, 'Sub - Analyze', 2, 'completed', 'low', 100, 67, 15, 43, 837);
                    

            INSERT INTO phases (id, project_id, title)
            VALUES (44, 15, 'Design');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (842, 'Fix workflow', 15, 'in_progress', 'urgent', 50, 0, 15, 44);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (843, 'Sub - Test', 2, 'completed', 'high', 100, 87, 15, 44, 842);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (844, 'Sub - Document', 15, 'pending', 'normal', 0, 0, 15, 44, 842);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (845, 'Design database', 18, 'in_progress', 'high', 88, 0, 15, 44);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (846, 'Sub - Design', 15, 'completed', 'low', 100, 92, 15, 44, 845);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (847, 'Sub - Document', 2, 'in_progress', 'high', 75, 0, 15, 44, 845);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (848, 'Refactor API', 17, 'completed', 'urgent', 100, 98, 15, 44);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (849, 'Sub - Analyze', 15, 'completed', 'normal', 100, 91, 15, 44, 848);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (850, 'Sub - Optimize', 15, 'completed', 'urgent', 100, 83, 15, 44, 848);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (851, 'Refactor API', 17, 'completed', 'low', 100, 89, 15, 44);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (852, 'Sub - Fix', 18, 'completed', 'normal', 100, 57, 15, 44, 851);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (853, 'Sub - Implement', 15, 'completed', 'low', 100, 93, 15, 44, 851);
                    

            INSERT INTO phases (id, project_id, title)
            VALUES (45, 15, 'Deployment');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (854, 'Implement UI', 17, 'in_progress', 'urgent', 82, 0, 15, 45);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (855, 'Sub - Optimize', 15, 'in_progress', 'normal', 75, 0, 15, 45, 854);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (856, 'Sub - Refactor', 2, 'in_progress', 'low', 50, 0, 15, 45, 854);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (857, 'Sub - Refactor', 17, 'completed', 'low', 100, 86, 15, 45, 854);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (858, 'Sub - Optimize', 17, 'completed', 'urgent', 100, 89, 15, 45, 854);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (859, 'Review workflow', 17, 'in_progress', 'urgent', 34, 0, 15, 45);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (860, 'Sub - Optimize', 2, 'in_progress', 'low', 25, 0, 15, 45, 859);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (861, 'Sub - Document', 18, 'in_progress', 'low', 75, 0, 15, 45, 859);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (862, 'Sub - Refactor', 2, 'pending', 'low', 0, 0, 15, 45, 859);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (863, 'Fix module', 15, 'in_progress', 'normal', 50, 0, 15, 45);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (864, 'Sub - Design', 17, 'in_progress', 'normal', 50, 0, 15, 45, 863);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (865, 'Sub - Test', 2, 'completed', 'urgent', 100, 70, 15, 45, 863);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (866, 'Sub - Fix', 2, 'pending', 'urgent', 0, 0, 15, 45, 863);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (867, 'Fix dashboard', 2, 'in_progress', 'urgent', 38, 0, 15, 45);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (868, 'Sub - Document', 18, 'in_progress', 'urgent', 25, 0, 15, 45, 867);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (869, 'Sub - Design', 18, 'in_progress', 'low', 50, 0, 15, 45, 867);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (870, 'Implement service', 15, 'in_progress', 'high', 88, 0, 15, 45);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (871, 'Sub - Refactor', 15, 'completed', 'normal', 100, 62, 15, 45, 870);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (872, 'Sub - Document', 15, 'in_progress', 'high', 75, 0, 15, 45, 870);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (873, 'Fix database', 18, 'in_progress', 'low', 44, 0, 15, 45);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (874, 'Sub - Optimize', 2, 'in_progress', 'low', 50, 0, 15, 45, 873);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (875, 'Sub - Optimize', 17, 'pending', 'normal', 0, 0, 15, 45, 873);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (876, 'Sub - Document', 18, 'in_progress', 'high', 75, 0, 15, 45, 873);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (877, 'Sub - Optimize', 18, 'in_progress', 'normal', 50, 0, 15, 45, 873);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (878, 'Fix service', 18, 'in_progress', 'low', 75, 0, 15, 45);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (879, 'Sub - Implement', 12, 'completed', 'urgent', 100, 80, 15, 45, 878);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (880, 'Sub - Design', 17, 'in_progress', 'high', 50, 0, 15, 45, 878);
                    

            INSERT INTO phases (id, project_id, title)
            VALUES (46, 15, 'Deployment');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (881, 'Test workflow', 12, 'in_progress', 'normal', 67, 0, 15, 46);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (882, 'Sub - Implement', 18, 'in_progress', 'low', 25, 0, 15, 46, 881);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (883, 'Sub - Test', 2, 'in_progress', 'normal', 75, 0, 15, 46, 881);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (884, 'Sub - Fix', 18, 'completed', 'urgent', 100, 88, 15, 46, 881);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (885, 'Analyze pipeline', 15, 'completed', 'urgent', 100, 92, 15, 46);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (886, 'Sub - Refactor', 17, 'completed', 'normal', 100, 80, 15, 46, 885);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (887, 'Refactor dashboard', 15, 'in_progress', 'high', 50, 0, 15, 46);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (888, 'Sub - Deploy', 2, 'pending', 'low', 0, 0, 15, 46, 887);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (889, 'Sub - Implement', 2, 'completed', 'urgent', 100, 80, 15, 46, 887);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (890, 'Optimize UI', 15, 'completed', 'high', 100, 70, 15, 46);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (891, 'Sub - Deploy', 17, 'completed', 'low', 100, 61, 15, 46, 890);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (892, 'Sub - Design', 18, 'completed', 'high', 100, 95, 15, 46, 890);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (893, 'Sub - Analyze', 2, 'completed', 'low', 100, 72, 15, 46, 890);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (894, 'Sub - Optimize', 17, 'completed', 'normal', 100, 87, 15, 46, 890);
                    

        INSERT INTO projects (id, title, staff_id, status, percentage, start_date, due_date)
        VALUES (16, 'Scalable Platform 16', 3, 'active', 69, '2025-06-07', '2026-02-05');
        

            INSERT INTO phases (id, project_id, title)
            VALUES (47, 16, 'Planning');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (895, 'Analyze UI', 29, 'in_progress', 'low', 50, 0, 16, 47);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (896, 'Sub - Refactor', 8, 'in_progress', 'normal', 50, 0, 16, 47, 895);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (897, 'Refactor dashboard', 10, 'completed', 'urgent', 100, 69, 16, 47);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (898, 'Sub - Deploy', 10, 'completed', 'urgent', 100, 69, 16, 47, 897);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (899, 'Deploy pipeline', 25, 'in_progress', 'high', 92, 0, 16, 47);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (900, 'Sub - Review', 11, 'in_progress', 'urgent', 75, 0, 16, 47, 899);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (901, 'Sub - Implement', 8, 'completed', 'low', 100, 65, 16, 47, 899);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (902, 'Sub - Deploy', 25, 'completed', 'normal', 100, 80, 16, 47, 899);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (903, 'Optimize UI', 8, 'in_progress', 'normal', 75, 0, 16, 47);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (904, 'Sub - Optimize', 25, 'in_progress', 'urgent', 50, 0, 16, 47, 903);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (905, 'Sub - Refactor', 10, 'completed', 'high', 100, 84, 16, 47, 903);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (906, 'Sub - Optimize', 29, 'in_progress', 'urgent', 75, 0, 16, 47, 903);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (907, 'Optimize database', 29, 'in_progress', 'urgent', 57, 0, 16, 47);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (908, 'Sub - Document', 10, 'pending', 'urgent', 0, 0, 16, 47, 907);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (909, 'Sub - Implement', 10, 'in_progress', 'urgent', 25, 0, 16, 47, 907);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (910, 'Sub - Document', 10, 'completed', 'normal', 100, 93, 16, 47, 907);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (911, 'Sub - Deploy', 25, 'completed', 'low', 100, 63, 16, 47, 907);
                    

            INSERT INTO phases (id, project_id, title)
            VALUES (48, 16, 'Testing');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (912, 'Analyze endpoint', 11, 'completed', 'normal', 100, 98, 16, 48);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (913, 'Sub - Analyze', 10, 'completed', 'low', 100, 57, 16, 48, 912);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (914, 'Sub - Implement', 3, 'completed', 'high', 100, 92, 16, 48, 912);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (915, 'Sub - Review', 8, 'completed', 'high', 100, 80, 16, 48, 912);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (916, 'Sub - Optimize', 10, 'completed', 'normal', 100, 82, 16, 48, 912);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (917, 'Document workflow', 25, 'in_progress', 'normal', 25, 0, 16, 48);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (918, 'Sub - Test', 8, 'in_progress', 'low', 25, 0, 16, 48, 917);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (919, 'Test module', 3, 'completed', 'urgent', 100, 70, 16, 48);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (920, 'Sub - Design', 11, 'completed', 'high', 100, 57, 16, 48, 919);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (921, 'Analyze dashboard', 11, 'pending', 'urgent', 0, 0, 16, 48);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (922, 'Sub - Test', 22, 'pending', 'high', 0, 0, 16, 48, 921);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (923, 'Refactor workflow', 10, 'in_progress', 'normal', 75, 0, 16, 48);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (924, 'Sub - Optimize', 29, 'in_progress', 'normal', 50, 0, 16, 48, 923);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (925, 'Sub - Deploy', 25, 'completed', 'low', 100, 97, 16, 48, 923);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (926, 'Document API', 10, 'in_progress', 'normal', 84, 0, 16, 48);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (927, 'Sub - Document', 3, 'completed', 'urgent', 100, 63, 16, 48, 926);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (928, 'Sub - Test', 10, 'in_progress', 'normal', 50, 0, 16, 48, 926);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (929, 'Sub - Refactor', 25, 'completed', 'urgent', 100, 72, 16, 48, 926);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (930, 'Test workflow', 8, 'in_progress', 'normal', 50, 0, 16, 48);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (931, 'Sub - Deploy', 8, 'completed', 'normal', 100, 95, 16, 48, 930);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (932, 'Sub - Optimize', 3, 'in_progress', 'normal', 25, 0, 16, 48, 930);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (933, 'Sub - Fix', 29, 'pending', 'urgent', 0, 0, 16, 48, 930);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (934, 'Sub - Fix', 3, 'in_progress', 'low', 75, 0, 16, 48, 930);
                    

            INSERT INTO phases (id, project_id, title)
            VALUES (49, 16, 'Testing');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (935, 'Test API', 3, 'in_progress', 'low', 75, 0, 16, 49);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (936, 'Sub - Test', 29, 'in_progress', 'high', 75, 0, 16, 49, 935);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (937, 'Analyze endpoint', 25, 'completed', 'high', 100, 100, 16, 49);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (938, 'Sub - Deploy', 10, 'completed', 'high', 100, 62, 16, 49, 937);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (939, 'Sub - Optimize', 8, 'completed', 'high', 100, 63, 16, 49, 937);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (940, 'Sub - Analyze', 3, 'completed', 'urgent', 100, 88, 16, 49, 937);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (941, 'Optimize dashboard', 29, 'in_progress', 'high', 75, 0, 16, 49);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (942, 'Sub - Refactor', 11, 'completed', 'high', 100, 93, 16, 49, 941);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (943, 'Sub - Refactor', 29, 'completed', 'normal', 100, 82, 16, 49, 941);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (944, 'Sub - Review', 10, 'in_progress', 'urgent', 25, 0, 16, 49, 941);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (945, 'Sub - Refactor', 10, 'in_progress', 'high', 75, 0, 16, 49, 941);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (946, 'Implement database', 29, 'in_progress', 'high', 50, 0, 16, 49);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (947, 'Sub - Review', 8, 'pending', 'high', 0, 0, 16, 49, 946);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (948, 'Sub - Implement', 3, 'in_progress', 'urgent', 50, 0, 16, 49, 946);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (949, 'Sub - Deploy', 22, 'completed', 'normal', 100, 78, 16, 49, 946);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (950, 'Sub - Review', 29, 'in_progress', 'low', 50, 0, 16, 49, 946);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (951, 'Refactor module', 11, 'in_progress', 'normal', 44, 0, 16, 49);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (952, 'Sub - Refactor', 29, 'completed', 'normal', 100, 94, 16, 49, 951);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (953, 'Sub - Fix', 11, 'pending', 'low', 0, 0, 16, 49, 951);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (954, 'Sub - Document', 10, 'pending', 'urgent', 0, 0, 16, 49, 951);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (955, 'Sub - Implement', 25, 'in_progress', 'high', 75, 0, 16, 49, 951);
                    

        INSERT INTO projects (id, title, staff_id, status, percentage, start_date, due_date)
        VALUES (17, 'Realtime Dashboard 17', 5, 'active', 59, '2023-10-18', '2024-08-16');
        

            INSERT INTO phases (id, project_id, title)
            VALUES (50, 17, 'Deployment');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (956, 'Fix pipeline', 14, 'in_progress', 'normal', 69, 0, 17, 50);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (957, 'Sub - Design', 5, 'completed', 'high', 100, 89, 17, 50, 956);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (958, 'Sub - Refactor', 5, 'completed', 'high', 100, 83, 17, 50, 956);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (959, 'Sub - Optimize', 5, 'in_progress', 'low', 25, 0, 17, 50, 956);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (960, 'Sub - Document', 5, 'in_progress', 'low', 50, 0, 17, 50, 956);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (961, 'Optimize endpoint', 28, 'in_progress', 'normal', 25, 0, 17, 50);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (962, 'Sub - Implement', 5, 'in_progress', 'high', 25, 0, 17, 50, 961);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (963, 'Implement service', 5, 'in_progress', 'urgent', 50, 0, 17, 50);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (964, 'Sub - Refactor', 9, 'pending', 'low', 0, 0, 17, 50, 963);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (965, 'Sub - Design', 28, 'completed', 'high', 100, 78, 17, 50, 963);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (966, 'Optimize UI', 5, 'pending', 'high', 0, 0, 17, 50);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (967, 'Sub - Review', 14, 'pending', 'high', 0, 0, 17, 50, 966);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (968, 'Analyze UI', 23, 'in_progress', 'low', 50, 0, 17, 50);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (969, 'Sub - Document', 28, 'completed', 'low', 100, 79, 17, 50, 968);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (970, 'Sub - Implement', 5, 'pending', 'low', 0, 0, 17, 50, 968);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (971, 'Refactor service', 28, 'completed', 'high', 100, 85, 17, 50);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (972, 'Sub - Refactor', 14, 'completed', 'normal', 100, 60, 17, 50, 971);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (973, 'Analyze database', 14, 'in_progress', 'high', 50, 0, 17, 50);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (974, 'Sub - Implement', 28, 'completed', 'normal', 100, 75, 17, 50, 973);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (975, 'Sub - Document', 5, 'pending', 'low', 0, 0, 17, 50, 973);
                    

            INSERT INTO phases (id, project_id, title)
            VALUES (51, 17, 'Planning');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (976, 'Review service', 5, 'in_progress', 'normal', 50, 0, 17, 51);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (977, 'Sub - Review', 23, 'completed', 'low', 100, 80, 17, 51, 976);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (978, 'Sub - Implement', 23, 'pending', 'high', 0, 0, 17, 51, 976);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (979, 'Analyze API', 9, 'in_progress', 'high', 75, 0, 17, 51);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (980, 'Sub - Document', 5, 'completed', 'high', 100, 71, 17, 51, 979);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (981, 'Sub - Review', 5, 'in_progress', 'high', 50, 0, 17, 51, 979);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (982, 'Design database', 9, 'in_progress', 'low', 42, 0, 17, 51);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (983, 'Sub - Refactor', 23, 'in_progress', 'low', 25, 0, 17, 51, 982);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (984, 'Sub - Deploy', 14, 'completed', 'urgent', 100, 92, 17, 51, 982);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (985, 'Sub - Document', 14, 'pending', 'normal', 0, 0, 17, 51, 982);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (986, 'Implement endpoint', 9, 'completed', 'normal', 100, 87, 17, 51);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (987, 'Sub - Refactor', 14, 'completed', 'normal', 100, 87, 17, 51, 986);
                    

        INSERT INTO projects (id, title, staff_id, status, percentage, start_date, due_date)
        VALUES (18, 'Smart Platform 18', 3, 'active', 68, '2022-06-29', '2023-03-01');
        

            INSERT INTO phases (id, project_id, title)
            VALUES (52, 18, 'Design');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (988, 'Review module', 22, 'in_progress', 'normal', 25, 0, 18, 52);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (989, 'Sub - Refactor', 11, 'pending', 'normal', 0, 0, 18, 52, 988);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (990, 'Sub - Document', 8, 'in_progress', 'urgent', 25, 0, 18, 52, 988);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (991, 'Sub - Design', 25, 'pending', 'urgent', 0, 0, 18, 52, 988);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (992, 'Sub - Design', 11, 'in_progress', 'high', 75, 0, 18, 52, 988);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (993, 'Implement UI', 11, 'in_progress', 'low', 50, 0, 18, 52);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (994, 'Sub - Fix', 25, 'pending', 'urgent', 0, 0, 18, 52, 993);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (995, 'Sub - Implement', 25, 'in_progress', 'high', 50, 0, 18, 52, 993);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (996, 'Sub - Test', 22, 'completed', 'normal', 100, 94, 18, 52, 993);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (997, 'Design API', 22, 'in_progress', 'low', 75, 0, 18, 52);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (998, 'Sub - Fix', 3, 'in_progress', 'normal', 75, 0, 18, 52, 997);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (999, 'Sub - Optimize', 10, 'in_progress', 'high', 75, 0, 18, 52, 997);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1000, 'Test service', 8, 'completed', 'high', 100, 72, 18, 52);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1001, 'Sub - Analyze', 11, 'completed', 'low', 100, 60, 18, 52, 1000);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1002, 'Refactor service', 10, 'completed', 'low', 100, 68, 18, 52);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1003, 'Sub - Review', 22, 'completed', 'urgent', 100, 65, 18, 52, 1002);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1004, 'Implement dashboard', 29, 'in_progress', 'low', 34, 0, 18, 52);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1005, 'Sub - Analyze', 11, 'in_progress', 'normal', 75, 0, 18, 52, 1004);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1006, 'Sub - Optimize', 29, 'in_progress', 'normal', 25, 0, 18, 52, 1004);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1007, 'Sub - Document', 11, 'pending', 'urgent', 0, 0, 18, 52, 1004);
                    

            INSERT INTO phases (id, project_id, title)
            VALUES (53, 18, 'Design');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1008, 'Document endpoint', 11, 'in_progress', 'normal', 63, 0, 18, 53);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1009, 'Sub - Test', 8, 'in_progress', 'normal', 25, 0, 18, 53, 1008);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1010, 'Sub - Analyze', 3, 'completed', 'low', 100, 90, 18, 53, 1008);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1011, 'Refactor API', 11, 'in_progress', 'normal', 75, 0, 18, 53);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1012, 'Sub - Deploy', 11, 'in_progress', 'normal', 75, 0, 18, 53, 1011);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1013, 'Sub - Design', 22, 'in_progress', 'normal', 50, 0, 18, 53, 1011);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1014, 'Sub - Refactor', 10, 'completed', 'high', 100, 65, 18, 53, 1011);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1015, 'Test API', 10, 'completed', 'high', 100, 89, 18, 53);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1016, 'Sub - Fix', 29, 'completed', 'high', 100, 69, 18, 53, 1015);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1017, 'Design workflow', 11, 'in_progress', 'urgent', 75, 0, 18, 53);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1018, 'Sub - Design', 25, 'in_progress', 'normal', 25, 0, 18, 53, 1017);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1019, 'Sub - Fix', 29, 'completed', 'urgent', 100, 60, 18, 53, 1017);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1020, 'Sub - Design', 25, 'completed', 'urgent', 100, 75, 18, 53, 1017);
                    

            INSERT INTO phases (id, project_id, title)
            VALUES (54, 18, 'Planning');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1021, 'Implement database', 11, 'in_progress', 'low', 88, 0, 18, 54);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1022, 'Sub - Optimize', 25, 'completed', 'low', 100, 50, 18, 54, 1021);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1023, 'Sub - Review', 25, 'completed', 'urgent', 100, 51, 18, 54, 1021);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1024, 'Sub - Deploy', 3, 'completed', 'normal', 100, 53, 18, 54, 1021);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1025, 'Sub - Test', 25, 'in_progress', 'low', 50, 0, 18, 54, 1021);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1026, 'Refactor dashboard', 25, 'completed', 'urgent', 100, 61, 18, 54);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1027, 'Sub - Design', 3, 'completed', 'high', 100, 96, 18, 54, 1026);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1028, 'Review dashboard', 22, 'in_progress', 'normal', 75, 0, 18, 54);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1029, 'Sub - Deploy', 10, 'in_progress', 'normal', 75, 0, 18, 54, 1028);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1030, 'Design API', 10, 'pending', 'normal', 0, 0, 18, 54);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1031, 'Sub - Fix', 25, 'pending', 'low', 0, 0, 18, 54, 1030);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1032, 'Fix endpoint', 22, 'in_progress', 'urgent', 25, 0, 18, 54);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1033, 'Sub - Fix', 22, 'pending', 'high', 0, 0, 18, 54, 1032);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1034, 'Sub - Fix', 25, 'in_progress', 'low', 50, 0, 18, 54, 1032);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1035, 'Fix database', 10, 'completed', 'high', 100, 65, 18, 54);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1036, 'Sub - Review', 8, 'completed', 'urgent', 100, 84, 18, 54, 1035);
                    

            INSERT INTO phases (id, project_id, title)
            VALUES (55, 18, 'Deployment');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1037, 'Optimize database', 29, 'in_progress', 'high', 82, 0, 18, 55);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1038, 'Sub - Document', 8, 'completed', 'low', 100, 66, 18, 55, 1037);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1039, 'Sub - Deploy', 3, 'in_progress', 'high', 25, 0, 18, 55, 1037);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1040, 'Sub - Refactor', 10, 'completed', 'low', 100, 74, 18, 55, 1037);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1041, 'Sub - Design', 22, 'completed', 'low', 100, 68, 18, 55, 1037);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1042, 'Design database', 3, 'completed', 'urgent', 100, 75, 18, 55);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1043, 'Sub - Design', 3, 'completed', 'high', 100, 61, 18, 55, 1042);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1044, 'Document dashboard', 10, 'in_progress', 'high', 25, 0, 18, 55);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1045, 'Sub - Implement', 8, 'in_progress', 'high', 50, 0, 18, 55, 1044);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1046, 'Sub - Design', 8, 'pending', 'low', 0, 0, 18, 55, 1044);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1047, 'Document dashboard', 25, 'in_progress', 'low', 75, 0, 18, 55);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1048, 'Sub - Deploy', 22, 'in_progress', 'high', 75, 0, 18, 55, 1047);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1049, 'Sub - Design', 3, 'completed', 'high', 100, 85, 18, 55, 1047);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1050, 'Sub - Deploy', 8, 'in_progress', 'low', 50, 0, 18, 55, 1047);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1051, 'Optimize UI', 10, 'in_progress', 'urgent', 50, 0, 18, 55);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1052, 'Sub - Test', 8, 'in_progress', 'high', 75, 0, 18, 55, 1051);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1053, 'Sub - Implement', 25, 'pending', 'normal', 0, 0, 18, 55, 1051);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1054, 'Sub - Deploy', 8, 'completed', 'urgent', 100, 80, 18, 55, 1051);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1055, 'Sub - Refactor', 11, 'in_progress', 'urgent', 25, 0, 18, 55, 1051);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1056, 'Refactor dashboard', 10, 'in_progress', 'normal', 38, 0, 18, 55);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1057, 'Sub - Fix', 11, 'in_progress', 'low', 50, 0, 18, 55, 1056);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1058, 'Sub - Document', 10, 'pending', 'normal', 0, 0, 18, 55, 1056);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1059, 'Sub - Document', 25, 'pending', 'high', 0, 0, 18, 55, 1056);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1060, 'Sub - Optimize', 22, 'completed', 'high', 100, 67, 18, 55, 1056);
                    

        INSERT INTO projects (id, title, staff_id, status, percentage, start_date, due_date)
        VALUES (19, 'Cloud System 19', 3, 'active', 64, '2023-04-04', '2023-11-22');
        

            INSERT INTO phases (id, project_id, title)
            VALUES (56, 19, 'Testing');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1061, 'Design API', 11, 'in_progress', 'low', 67, 0, 19, 56);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1062, 'Sub - Design', 25, 'completed', 'urgent', 100, 95, 19, 56, 1061);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1063, 'Sub - Analyze', 22, 'in_progress', 'normal', 75, 0, 19, 56, 1061);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1064, 'Sub - Design', 3, 'in_progress', 'urgent', 25, 0, 19, 56, 1061);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1065, 'Fix database', 8, 'in_progress', 'high', 69, 0, 19, 56);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1066, 'Sub - Deploy', 8, 'completed', 'high', 100, 56, 19, 56, 1065);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1067, 'Sub - Optimize', 25, 'in_progress', 'high', 25, 0, 19, 56, 1065);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1068, 'Sub - Review', 8, 'in_progress', 'high', 50, 0, 19, 56, 1065);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1069, 'Sub - Analyze', 10, 'completed', 'urgent', 100, 98, 19, 56, 1065);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1070, 'Test module', 22, 'in_progress', 'urgent', 84, 0, 19, 56);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1071, 'Sub - Design', 29, 'completed', 'normal', 100, 97, 19, 56, 1070);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1072, 'Sub - Optimize', 10, 'completed', 'high', 100, 99, 19, 56, 1070);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1073, 'Sub - Document', 25, 'in_progress', 'normal', 50, 0, 19, 56, 1070);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1074, 'Design service', 22, 'in_progress', 'high', 50, 0, 19, 56);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1075, 'Sub - Refactor', 29, 'in_progress', 'urgent', 50, 0, 19, 56, 1074);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1076, 'Refactor database', 22, 'in_progress', 'high', 38, 0, 19, 56);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1077, 'Sub - Design', 11, 'in_progress', 'urgent', 75, 0, 19, 56, 1076);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1078, 'Sub - Analyze', 8, 'pending', 'urgent', 0, 0, 19, 56, 1076);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1079, 'Deploy UI', 29, 'completed', 'low', 100, 78, 19, 56);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1080, 'Sub - Refactor', 10, 'completed', 'urgent', 100, 76, 19, 56, 1079);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1081, 'Sub - Document', 10, 'completed', 'high', 100, 76, 19, 56, 1079);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1082, 'Sub - Review', 10, 'completed', 'low', 100, 82, 19, 56, 1079);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1083, 'Document API', 11, 'completed', 'urgent', 100, 74, 19, 56);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1084, 'Sub - Refactor', 22, 'completed', 'low', 100, 70, 19, 56, 1083);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1085, 'Sub - Implement', 25, 'completed', 'urgent', 100, 54, 19, 56, 1083);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1086, 'Sub - Implement', 29, 'completed', 'urgent', 100, 69, 19, 56, 1083);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1087, 'Sub - Implement', 29, 'completed', 'low', 100, 78, 19, 56, 1083);
                    

            INSERT INTO phases (id, project_id, title)
            VALUES (57, 19, 'Testing');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1088, 'Fix dashboard', 29, 'pending', 'normal', 0, 0, 19, 57);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1089, 'Sub - Implement', 22, 'pending', 'low', 0, 0, 19, 57, 1088);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1090, 'Implement database', 10, 'completed', 'urgent', 100, 74, 19, 57);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1091, 'Sub - Refactor', 8, 'completed', 'low', 100, 70, 19, 57, 1090);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1092, 'Sub - Implement', 11, 'completed', 'normal', 100, 61, 19, 57, 1090);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1093, 'Design dashboard', 10, 'in_progress', 'high', 50, 0, 19, 57);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1094, 'Sub - Test', 22, 'in_progress', 'high', 50, 0, 19, 57, 1093);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1095, 'Sub - Analyze', 22, 'in_progress', 'low', 50, 0, 19, 57, 1093);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1096, 'Deploy pipeline', 10, 'in_progress', 'low', 63, 0, 19, 57);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1097, 'Sub - Fix', 8, 'completed', 'urgent', 100, 57, 19, 57, 1096);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1098, 'Sub - Fix', 22, 'in_progress', 'high', 25, 0, 19, 57, 1096);
                    

        INSERT INTO projects (id, title, staff_id, status, percentage, start_date, due_date)
        VALUES (20, 'Cloud Dashboard 20', 4, 'active', 67, '2020-01-09', '2021-01-02');
        

            INSERT INTO phases (id, project_id, title)
            VALUES (58, 20, 'Testing');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1099, 'Optimize pipeline', 20, 'in_progress', 'normal', 84, 0, 20, 58);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1100, 'Sub - Design', 19, 'in_progress', 'normal', 50, 0, 20, 58, 1099);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1101, 'Sub - Implement', 26, 'completed', 'high', 100, 59, 20, 58, 1099);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1102, 'Sub - Test', 26, 'completed', 'normal', 100, 72, 20, 58, 1099);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1103, 'Document module', 4, 'in_progress', 'low', 67, 0, 20, 58);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1104, 'Sub - Review', 20, 'in_progress', 'normal', 25, 0, 20, 58, 1103);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1105, 'Sub - Analyze', 27, 'completed', 'low', 100, 79, 20, 58, 1103);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1106, 'Sub - Test', 21, 'in_progress', 'normal', 75, 0, 20, 58, 1103);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1107, 'Deploy database', 27, 'in_progress', 'urgent', 38, 0, 20, 58);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1108, 'Sub - Fix', 27, 'in_progress', 'normal', 25, 0, 20, 58, 1107);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1109, 'Sub - Refactor', 13, 'in_progress', 'urgent', 50, 0, 20, 58, 1107);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1110, 'Optimize pipeline', 26, 'in_progress', 'high', 50, 0, 20, 58);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1111, 'Sub - Document', 27, 'completed', 'low', 100, 73, 20, 58, 1110);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1112, 'Sub - Test', 4, 'pending', 'low', 0, 0, 20, 58, 1110);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1113, 'Test endpoint', 19, 'in_progress', 'high', 92, 0, 20, 58);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1114, 'Sub - Implement', 19, 'completed', 'normal', 100, 67, 20, 58, 1113);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1115, 'Sub - Test', 4, 'in_progress', 'high', 75, 0, 20, 58, 1113);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1116, 'Sub - Review', 27, 'completed', 'high', 100, 82, 20, 58, 1113);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1117, 'Deploy pipeline', 4, 'in_progress', 'high', 92, 0, 20, 58);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1118, 'Sub - Fix', 21, 'completed', 'high', 100, 88, 20, 58, 1117);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1119, 'Sub - Review', 27, 'in_progress', 'normal', 75, 0, 20, 58, 1117);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1120, 'Sub - Analyze', 13, 'completed', 'low', 100, 59, 20, 58, 1117);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1121, 'Document endpoint', 20, 'in_progress', 'low', 88, 0, 20, 58);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1122, 'Sub - Document', 4, 'in_progress', 'normal', 75, 0, 20, 58, 1121);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1123, 'Sub - Design', 4, 'completed', 'low', 100, 51, 20, 58, 1121);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1124, 'Sub - Test', 13, 'completed', 'high', 100, 50, 20, 58, 1121);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1125, 'Sub - Analyze', 4, 'in_progress', 'normal', 75, 0, 20, 58, 1121);
                    

            INSERT INTO phases (id, project_id, title)
            VALUES (59, 20, 'Development');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1126, 'Analyze service', 4, 'in_progress', 'high', 50, 0, 20, 59);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1127, 'Sub - Test', 26, 'in_progress', 'low', 75, 0, 20, 59, 1126);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1128, 'Sub - Design', 26, 'in_progress', 'low', 25, 0, 20, 59, 1126);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1129, 'Review endpoint', 13, 'completed', 'normal', 100, 86, 20, 59);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1130, 'Sub - Implement', 26, 'completed', 'urgent', 100, 69, 20, 59, 1129);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1131, 'Sub - Implement', 27, 'completed', 'urgent', 100, 88, 20, 59, 1129);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1132, 'Sub - Document', 26, 'completed', 'normal', 100, 57, 20, 59, 1129);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1133, 'Implement UI', 27, 'in_progress', 'urgent', 75, 0, 20, 59);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1134, 'Sub - Design', 13, 'in_progress', 'high', 25, 0, 20, 59, 1133);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1135, 'Sub - Document', 26, 'completed', 'normal', 100, 61, 20, 59, 1133);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1136, 'Sub - Analyze', 19, 'completed', 'high', 100, 66, 20, 59, 1133);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1137, 'Document module', 27, 'in_progress', 'low', 32, 0, 20, 59);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1138, 'Sub - Design', 19, 'completed', 'normal', 100, 70, 20, 59, 1137);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1139, 'Sub - Fix', 20, 'in_progress', 'low', 25, 0, 20, 59, 1137);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1140, 'Sub - Fix', 27, 'pending', 'high', 0, 0, 20, 59, 1137);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1141, 'Sub - Fix', 21, 'pending', 'normal', 0, 0, 20, 59, 1137);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1142, 'Analyze module', 27, 'in_progress', 'urgent', 75, 0, 20, 59);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1143, 'Sub - Test', 20, 'in_progress', 'urgent', 75, 0, 20, 59, 1142);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1144, 'Sub - Implement', 26, 'in_progress', 'high', 75, 0, 20, 59, 1142);
                    

            INSERT INTO phases (id, project_id, title)
            VALUES (60, 20, 'Planning');
            

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1145, 'Optimize service', 4, 'in_progress', 'urgent', 25, 0, 20, 60);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1146, 'Sub - Review', 19, 'in_progress', 'high', 25, 0, 20, 60, 1145);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1147, 'Sub - Analyze', 26, 'in_progress', 'low', 25, 0, 20, 60, 1145);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1148, 'Implement UI', 27, 'in_progress', 'low', 25, 0, 20, 60);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1149, 'Sub - Deploy', 20, 'in_progress', 'low', 25, 0, 20, 60, 1148);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1150, 'Fix pipeline', 27, 'in_progress', 'high', 63, 0, 20, 60);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1151, 'Sub - Document', 4, 'completed', 'high', 100, 64, 20, 60, 1150);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1152, 'Sub - Fix', 19, 'in_progress', 'urgent', 50, 0, 20, 60, 1150);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1153, 'Sub - Optimize', 20, 'pending', 'normal', 0, 0, 20, 60, 1150);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1154, 'Sub - Implement', 26, 'completed', 'normal', 100, 89, 20, 60, 1150);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1155, 'Review endpoint', 20, 'completed', 'urgent', 100, 65, 20, 60);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1156, 'Sub - Fix', 26, 'completed', 'normal', 100, 79, 20, 60, 1155);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1157, 'Sub - Document', 4, 'completed', 'urgent', 100, 74, 20, 60, 1155);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1158, 'Refactor service', 26, 'in_progress', 'urgent', 82, 0, 20, 60);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1159, 'Sub - Document', 20, 'in_progress', 'low', 75, 0, 20, 60, 1158);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1160, 'Sub - Analyze', 21, 'completed', 'high', 100, 53, 20, 60, 1158);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1161, 'Sub - Fix', 13, 'completed', 'normal', 100, 58, 20, 60, 1158);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1162, 'Sub - Analyze', 26, 'in_progress', 'urgent', 50, 0, 20, 60, 1158);
                    

                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES (1163, 'Document API', 20, 'in_progress', 'low', 57, 0, 20, 60);
                

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1164, 'Sub - Implement', 26, 'in_progress', 'normal', 25, 0, 20, 60, 1163);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1165, 'Sub - Fix', 21, 'completed', 'normal', 100, 53, 20, 60, 1163);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1166, 'Sub - Deploy', 4, 'completed', 'high', 100, 79, 20, 60, 1163);
                    

                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES (1167, 'Sub - Test', 4, 'pending', 'normal', 0, 0, 20, 60, 1163);
                    