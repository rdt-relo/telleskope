<?php
require_once __DIR__.'/../include/Company.php';
class CompanyTests extends \PHPUnit\Framework\TestCase
{
    public function testOne(){
        $company = Company::GetCompany(1);
        $this->assertEquals(1, $company->id);
    }

}