<?php

class ContentCest {

  public function testExternalParentLinks(AcceptanceTester $I) {
   $I->createEntity([
      'title' => 'Foo',
      'link' => 'http://stanford.edu',
    ], 'menu_link_content');
   $I->logInWithRole('administrator');
   $I->amOnPage('/admin/structure/menu/manage/main/add');
   $I->fillField('Menu link title', 'Bar');
   $I->fillField('Link', 'http://stanford.edu');
   $I->selectOption('Parent link','-- Foo');
   $I->click('Save');
   $I->canSee('The menu link has been saved');
  }

}
