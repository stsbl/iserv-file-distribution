/**
 * Author:  Felix Jacobi
 * Created: 28.11.2016
 * License: MIT license <https://opensource.org/licenses/MIT> 
 */

CREATE TABLE file_distribution (
    ID                  SERIAL          PRIMARY KEY,
    Title               TEXT            NOT NULL,
    Act                 TEXT            NOT NULL REFERENCES users(Act)
                                            ON UPDATE CASCADE
                                            ON DELETE CASCADE,
    IP                  INET            NOT NULL REFERENCES hosts(IP)
                                            ON UPDATE CASCADE
                                            ON DELETE CASCADE,
    ISOLATION           BOOLEAN         NOT NULL,
    UNIQUE(IP)
);

GRANT USAGE, SELECT ON "file_distribution_id_seq" TO "symfony";
GRANT SELECT, INSERT, UPDATE, DELETE ON "file_distribution" TO "symfony";