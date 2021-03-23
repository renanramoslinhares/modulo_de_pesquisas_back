CREATE DATABASE IF NOT EXISTS filterDB;

CREATE TABLE IF NOT EXISTS filterDB.Dimensions(
    DimensionID int NOT NULL AUTO_INCREMENT,
    Name varchar(60) NOT NULL,
    IsExcluded BOOL,
    CONSTRAINT PK_DimensionID PRIMARY KEY (DimensionID)
);

CREATE TABLE IF NOT EXISTS filterDB.Tasks(
    TaskID int NOT NULL AUTO_INCREMENT,
    DimensionID int NOT NULL,
    Text varchar(255) NOT NULL,
    Status ENUM('active', 'inactive') DEFAULT 'active',
    IsExcluded BOOL,
    PRIMARY KEY (TaskID),
    CONSTRAINT FK_DimensionID FOREIGN KEY (DimensionID) REFERENCES Dimensions (DimensionID)
);

INSERT INTO filterDB.Dimensions (Name) VALUES ('Bem-estar'), ('Carreira'), ('Estrutura');

INSERT INTO filterDB.Tasks (DimensionID, Text, Status) VALUES 
	(1, 'De 0 a 10, como você avalia a sua disposição para o dia?', 'active'),
	(2, 'O quanto você se sente atraído pelas oportunidades de carreira que a empresa oferece?', 'inactive'),
	(3, 'Quantos dias na semana você prefere trabalhar em home-office?', 'active');