CREATE DATABASE moviebook;

USE moviebook;
CREATE TABLE users
   (username VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    user_id INT NOT NULL AUTO_INCREMENT,
    PRIMARY KEY(user_id));
CREATE TABLE movies
    (movie_id VARCHAR(20) NOT NULL,
     title VARCHAR(255) NOT NULL,
     year INT,
     runtime INT,
     genres VARCHAR(255),
     PRIMARY KEY(movie_id));
CREATE TABLE reviews
    (review_id INT NOT NULL AUTO_INCREMENT,
     review_text TEXT NOT NULL,
     created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
     movie_id VARCHAR(20) NOT NULL,
     user_id INT NOT NULL,
     PRIMARY KEY(review_id),
     FOREIGN KEY(movie_id) REFERENCES movies(movie_id),
     FOREIGN KEY(user_id) REFERENCES users(user_id),
     UNIQUE (user_id, movie_id)); -- One review per user per movie
CREATE TABLE ratings
    (movie_id VARCHAR(20) NOT NULL,
     rating DECIMAL(3,1) NOT NULL,
     votes INT NOT NULL,
     PRIMARY KEY(movie_id),
     FOREIGN KEY(movie_id) REFERENCES movies(movie_id));
CREATE TABLE people
    (person_id VARCHAR(20) NOT NULL,
     name VARCHAR(255) NOT NULL,
     birth_year INT,
     death_year INT,
     PRIMARY KEY(person_id));
CREATE TABLE studios
    (studio_id INT NOT NULL AUTO_INCREMENT,
     studio_name VARCHAR(255) NOT NULL UNIQUE,
     PRIMARY KEY(studio_id));
CREATE TABLE genres
    (genre VARCHAR(255) NOT NULL,
     PRIMARY KEY(genre));
CREATE TABLE movie_actors
    (movie_id VARCHAR(20) NOT NULL,
     person_id VARCHAR(20) NOT NULL,
     PRIMARY KEY(movie_id, person_id),
     FOREIGN KEY(movie_id) REFERENCES movies(movie_id),
     FOREIGN KEY(person_id) REFERENCES people(person_id));
CREATE TABLE movie_directors
    (movie_id VARCHAR(20) NOT NULL,
     person_id VARCHAR(20) NOT NULL,
     PRIMARY KEY(movie_id, person_id),
     FOREIGN KEY(movie_id) REFERENCES movies(movie_id),
     FOREIGN KEY(person_id) REFERENCES people(person_id));
CREATE TABLE movie_studios
    (movie_id VARCHAR(20) NOT NULL,
     studio_id INT NOT NULL,
     PRIMARY KEY(movie_id, studio_id),
     FOREIGN KEY(movie_id) REFERENCES movies(movie_id),
     FOREIGN KEY(studio_id) REFERENCES studios(studio_id));
CREATE TABLE movie_genres
    (movie_id VARCHAR(20) NOT NULL,
     genre VARCHAR(255) NOT NULL,
     PRIMARY KEY(movie_id, genre),
     FOREIGN KEY(movie_id) REFERENCES movies(movie_id),
     FOREIGN KEY(genre) REFERENCES genres(genre));