<?php

class HomePageCest {

  /**
   * Validate the homepage loads.
   */
  public function testHomepage(AcceptanceTester $I) {
    $I->amOnPage('/');
    $I->canSeeResponseCodeIs(200);
    $I->logInWithRole('administrator');
    $I->amOnPage('/admin/structure');
    $I->canSeeResponseCodeIs(200);
  }

}
