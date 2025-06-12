<?php

// The services in this file are intended to be consumed over the network, e.g. over localhost
// or using private IP
// or using private host

require_once __DIR__ .'/../include/Company.php';

if (isset($_GET['seed_company_dictionary']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    CompanyDictionary::GetCompanyDictionary($_GET['seed_company_dictionary'] === 'reseed');
}