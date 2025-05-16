<?php
class ReviewModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Get reviews for a specific car
    public function getCarReviews($carId, $limit = 10, $offset = 0) {
        $query = "SELECT 
                    r.rating, 
                    r.review_text, 
                    r.review_date, 
                    u.username as reviewer_name
                  FROM user_car_reviews r
                  JOIN utenti u ON r.reviewer_id = u.id
                  WHERE r.car_id = $1
                  ORDER BY r.review_date DESC
                  LIMIT $2 OFFSET $3";
        
        $result = pg_query_params($this->conn, $query, array($carId, $limit, $offset));
        
        return pg_fetch_all($result);
    }

    // Submit a new review
    public function submitReview($sellerId, $reviewerId, $carId, $rating, $reviewText) {
        $query = "INSERT INTO user_car_reviews 
                  (seller_id, reviewer_id, car_id, rating, review_text) 
                  VALUES ($1, $2, $3, $4, $5)";
        
        $result = pg_query_params($this->conn, $query, array(
            $sellerId, 
            $reviewerId, 
            $carId, 
            $rating, 
            $reviewText
        ));
        
        return $result !== false;
    }

    // Get average rating for a seller
    public function getSellerAverageRating($sellerId)
    {
        $query = "SELECT AVG(rating) as average_rating FROM user_car_reviews WHERE seller_id = $1";
        $result = pg_query_params($this->conn, $query, array($sellerId));
        $row = pg_fetch_assoc($result);
        return $row && $row['average_rating'] !== null ? floatval($row['average_rating']) : null;
    }


    // Get reviews for a specific seller
    public function getSellerReviews($sellerId, $limit = 10, $offset = 0) {
        $query = "SELECT 
                    r.rating, 
                    r.review_text, 
                    r.review_date, 
                    u.username as reviewer_name
                  FROM user_car_reviews r
                  JOIN utenti u ON r.reviewer_id = u.id
                  WHERE r.seller_id = $1
                  ORDER BY r.review_date DESC
                  LIMIT $2 OFFSET $3";
        
        $result = pg_query_params($this->conn, $query, array($sellerId, $limit, $offset));
        
        return pg_fetch_all($result);
    }
}
?>
