CREATE DATABASE trs2wd;

USE trs2wd;

CREATE TABLE users (
    username VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    userId INT NOT NULL AUTO_INCREMENT,
    PRIMARY KEY (userId)
);

CREATE TABLE movies (
    movieId INT NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    releaseYear INT,
    runtimeMinutes INT,
    PRIMARY KEY (movieId)
);

CREATE TABLE people (
    personId INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    birthYear INT,
    deathYear INT,
    PRIMARY KEY (personId)
);

CREATE TABLE genres (
    genreId INT NOT NULL AUTO_INCREMENT,
    genreName VARCHAR(255) NOT NULL UNIQUE,
    PRIMARY KEY (genreId)
);

CREATE TABLE reviews (
    reviewId INT NOT NULL AUTO_INCREMENT,
    reviewText TEXT NOT NULL,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    movieId INT NOT NULL,
    userId INT NOT NULL,
    PRIMARY KEY (reviewId),
    FOREIGN KEY (movieId) REFERENCES movies(movieId),
    FOREIGN KEY (userId) REFERENCES users(userId),
    UNIQUE (userId, movieId)  -- One review per user per movie
);

CREATE TABLE ratings (
    ratingId INT NOT NULL AUTO_INCREMENT,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 10),  -- Double check 1-5 or 1-10
    movieId INT NOT NULL,
    userId INT NOT NULL,
    PRIMARY KEY (ratingId),
    FOREIGN KEY (movieId) REFERENCES movies(movieId),
    FOREIGN KEY (userId) REFERENCES users(userId)
);

CREATE TABLE writes (
    userId INT NOT NULL,
    reviewId INT NOT NULL,
    FOREIGN KEY (userId) REFERENCES users(userId),
    FOREIGN KEY (reviewId) REFERENCES reviews(reviewId),
    PRIMARY KEY (reviewId)
);

CREATE TABLE creates (
    userId INT NOT NULL,
    ratingId INT NOT NULL,
    FOREIGN KEY (userId) REFERENCES users(userId),
    FOREIGN KEY (ratingId) REFERENCES ratings(ratingId),
    PRIMARY KEY (ratingId)
);

CREATE TABLE has (
    movieId INT NOT NULL,
    reviewId INT NOT NULL,
    FOREIGN KEY (movieId) REFERENCES movies(movieId),
    FOREIGN KEY (reviewId) REFERENCES reviews(reviewId),
    PRIMARY KEY (reviewId)
);

CREATE TABLE possesses (
    movieId INT NOT NULL,
    ratingId INT NOT NULL,
    FOREIGN KEY (movieId) REFERENCES movies(movieId),
    FOREIGN KEY (ratingId) REFERENCES ratings(ratingId),
    PRIMARY KEY (ratingId)
);

CREATE TABLE movie_actors (
    movieId INT NOT NULL,
    personId INT NOT NULL,
    PRIMARY KEY (movieId, personId),
    FOREIGN KEY (movieId) REFERENCES movies(movieId),
    FOREIGN KEY (personId) REFERENCES people(personId)
);

CREATE TABLE movie_directors (
    movieId INT NOT NULL,
    personId INT NOT NULL,
    PRIMARY KEY (movieId, personId),
    FOREIGN KEY (movieId) REFERENCES movies(movieId),
    FOREIGN KEY (personId) REFERENCES people(personId)
);

CREATE TABLE movie_genres (
    movieId INT NOT NULL,
    genreId INT NOT NULL,
    PRIMARY KEY (movieId, genreId),
    FOREIGN KEY (movieId) REFERENCES movies(movieId),
    FOREIGN KEY (genreId) REFERENCES genres(genreId)
);
