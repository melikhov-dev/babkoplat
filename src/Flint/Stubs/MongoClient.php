<?php

if (!class_exists('MongoClient')) {
    class MongoClient extends Mongo {};
}
