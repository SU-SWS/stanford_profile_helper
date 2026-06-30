# Stanford Profile Helper

10.2.8
--------------------------------------------------------------------------------
_Release Date: 2026-06-29_
- Enhance next path revalidator with POST request.

10.2.6
--------------------------------------------------------------------------------
_Release Date: 2026-06-24_
- Fixed layout builder list builder class method arguments.

10.2.5
--------------------------------------------------------------------------------
_Release Date: 2026-06-08_
- D8CORE-8820: removed space adjustment from comma
- D8CORE-8647: Updated the js to work with VO command keys
- D8CORE-8721: Correcting spacing errors for Audio/Visual content type.
- D8CORE-8680: adding aria-label to the jump link.
- D8CORE-8283: Updated the search date color to su-color-black-70 to make it comply with a11y 4.5 minimum

10.2.4
--------------------------------------------------------------------------------
_Release Date: 2026-04-30_

- Changed trash path auto pattern

10.2.3
--------------------------------------------------------------------------------
_Release Date: 2026-04-28_

- Modify pathauto pattern for page if it is put into the trash
- Delete Algolia items that are in the trash

10.2.2
--------------------------------------------------------------------------------
_Release Date: 2026-04-17_

- D8CORE-8651: Requested anchor nav tweaks (#522)
- Fixed entity mapping for wordpress importer form

10.2.1
--------------------------------------------------------------------------------
_Release Date: 2026-02-20_

- Removed the overflow unset on filters and added 6px of top space to options

10.2.0
--------------------------------------------------------------------------------
_Release Date: 2026-02-19_

- Improve localist event orphan checker to check for deleted instances if the event exists (#512)
- Create layout library widget with image icon selection, duration widget, and transcript field changes (#510)
- Add anchor link navigation block (#480)
- Past Event list minor styling changes (#487)
- Media CT node, teaser, card styling (#482)
- Updated number of cards across on filtered lists (#486)
- Update siteimprove analytics

10.1.4
--------------------------------------------------------------------------------
_Release Date: 2025-12-09_

- Fixed error in Localist event lookup deletion.

10.1.3
--------------------------------------------------------------------------------
_Release Date: 2025-12-03_

- Fixed algolia html trimming for local debugging.

10.1.2
--------------------------------------------------------------------------------
_Release Date: 2025-11-25_

- Added space for the load more button on publication node (#474)
- Adjust embedded video styles to have correct aspect ratios

10.1.0
--------------------------------------------------------------------------------
_Release Date: 2025-11-20_

- Added intranet hook to remove item from algolia if they are restricted access
- D8CORE-8470 | remove margin spacing on radio button (#469)
- Check Localist and delete imported events that no longer exist (#468)
- D8CORE-8470 | align radio button to top with label (#466)
- D8CORE-8474 | fixup container query for quote typography (#467)
- D8CORE-8471 adjusted padding and border color (#464)
- changed the number of cards for the medium breakpoint (#465)
- added a min-width to keep radio button round (#463)
- D8CORE-8457 | display spotlight card images on smaller screens (#461)
- Updated filter column width and number of cards at large breakpoint (#459)
- D8CORE-8460 D8CORE-8237 | adjust left column filter spacing and radio styles; fix single teaser style (#460)
- D8CORE-8235 | adjust grid responsive for spotlight card list paragraph (#457)
- Provided autocomplete suggestions to viewfield arguments input (#458)
- Added wordpress migration UI and styles for media content type. (#442)
- adding styles for filtered spotlights (#455)
- D8CORE-8233 D8CORE-8235 D8CORE-8237 | style spotlight card for related spotlights, list, and teaser paragraph (#449)
- Font correction (#456)
- Corrected font family for news spotlight name (#450)
- Fixup plum color for hero banner color (#451)
- Remove whitespace from fontawesome links in the wysiwyg
- Change help text for news variants layout selection (#448)
- Support comma in stat card animation
- Removed event has already occurred from the list of past events. (#441)
- Adjust spacing to have full video appear when added to wysiwyg (#438)
- style spotlight variant for news content type (#437)


10.0.3
--------------------------------------------------------------------------------
_Release Date: 2025-10-15_

- Improve NextJS invalidation when a media or taxonomy term is changed (#444)
- D8CORE-8322 | fixup default overlay banner color (#443)
- Prepend uuid for algolia items with a hash of the site name
- D8CORE-8322 | Add banner overlay options and styles (#435)
- D8CORE-8227 | Rename title and _none option for layout selection (#440)
- D8CORE-8316 | Adjust events page details layout and styles (#436)
- Change login link destination to use only the pathname and not any query parameters (#439)
- D8CORE-8316 | adjust event details card spacing (#433)
- D8CORE-8196 | adjust card content order for a11y (#434)
- D8CORE-8160 D8CORE-8159 Style adjustments to opportunity page display (#432)
- Event subtitles a little less bold (#431)


10.0.2
--------------------------------------------------------------------------------
_Release Date: 2025-08-27_

- Fixed page redirect logic for site information.

10.0.1
--------------------------------------------------------------------------------
_Release Date: 2025-08-22_

- Adjusted update hook to update more configs from response_code_condition

10.0.0
--------------------------------------------------------------------------------
_Release Date: 2025-08-22_

- Added constraint for redirects to test for nodes in the trash (#427)
- Updated the search background color (#426)
- Move some plugins to stanford_fields module and add it as a dependency (#424)
- Fixed one column layout paragraph background color option
- Changed search page background color (#423)
- Added Sponsor to opportunity node (#416)
- Add logged in user information to drupalSettings  (#422)
- Add option for vertical dividers on 2 and 3 column layouts (#421)
- Move redirect user function from profile (#420)
- Upgrade event subscriber, hooks, and tests for Drupal 11 (#419)
- Move PDB blocks in layout builder to eliminate pdb_react module
- Updated line-height on a person node page "person links" (#417)
- Opportunity CTA button color correction (#418

9.10.3
--------------------------------------------------------------------------------
_Release Date: 2025-07-16_

- Revert basic auth global provider in favor of router subscriber modification for private file auth.

9.10.2
--------------------------------------------------------------------------------
_Release Date: 2025-07-16_

- Alter basic auth provider to be global for all routes

9.10.1
--------------------------------------------------------------------------------
_Release Date: 2025-06-19_

- UE-536: Corrected opportunity  node page mobile layout and some spacing issues

9.10.0
--------------------------------------------------------------------------------
_Release Date: 2025-06-13_

- Updating spacing on Opportunity page right column. (#404)
- Updated spacing on Events' contact to be a11y compliant (#407)
- Added visible/invisible state to stat card color fields in UI
- Updated stat card with additional colors
- Support trailing zeros after a decimal for stat card counter
- Added section margin and padding options. Adjusted section colors
- Animate stat card numbers
- Added stat card component & row layout background colors (#405)
- Add "Color Field" support for graphql field data

9.9.1
--------------------------------------------------------------------------------
_Release Date: 2025-03-19_

-D8CORE-8066 Adjust CAP org code taxonomy term population

9.9.0
--------------------------------------------------------------------------------
_Release Date: 2025-03-10_

- D8CORE-8031 Allow force displaying both regions on two column layout
- Adjust intranet settings for anonymous users redirect message
- Opportunities content type and views styles (#391)
- Adjust decoupled detector for editoria11y library
- adding spacing below the profile link when no other content (#390)
- Allow publishing home page for only admins
- styling the code block in ckeditor to look the same in editing, preview, and live page (#380)
- changed event schedule heading level for best order (#385)

9.8.14
--------------------------------------------------------------------------------
_Release Date: 2025-03-10_

- Fix string type to int type for book tree menu.

9.8.13
--------------------------------------------------------------------------------
_Release Date: 2025-03-07_

- Added sunet to site information cron
- changed size of mailto icon to match previous icon (#381)
- Accordions added a transparent background to icon (#376)
- Accordion add padding to prevent button overlaying text
- Styling for opportunities content type. (#353)
- Created a single collayout to added the filtered paragraph into (#378)
- updated the color of the caret on su-link--action for policy teaser (#372)
- formatted the spacing on search page (#374)
- fine tuning the news list (#377)
- adjustments to spacing on a person list (#371)
- adding space for touch targets on Events node taxonomy terms.  (#368)
- changed spacing on the basic page list (#369)
- Removed margin-bottom from the "This event has already occurred" (#370)

9.8.12
--------------------------------------------------------------------------------
_Release Date: 2025-02-11_

- Update hooks to support external link 2.0+ module

9.8.11
--------------------------------------------------------------------------------
_Release Date: 2025-02-07_

- Fix space between components in two and three column layouts.

9.8.10
--------------------------------------------------------------------------------
_Release Date: 2025-01-31_

- Refactor accordion to use button and aria attributes (#364)
- D8CORE-7722 design tweaks (#363)
- Fix layout paragraph columns grid to flex (#362)
- tighten spacing on news list (#359)
- margin bottom zero on last item (#358)
- tighten up event list spacing (#360)
- added aria-live: polite to the modal (#361)
- Add trash delete hook similar to node delete event
- removed max-width on intro text and drop cap text (#357)
- D8CORE-7696 Add small spaces between components in two and three column layouts
- removed medium and small quotes (#356)
- Replace field_widget_form_alter hook that was removed from core

9.8.9
--------------------------------------------------------------------------------
_Release Date: 2024-11-20_

- Fixed styles for people cards and lists.
- Added uuid tokens on entities and token support for nextjs entity paths.

9.8.8
--------------------------------------------------------------------------------
_Release Date: 2024-11-15_

- Updated stanford.edu to www.stanford.edu

9.8.7
--------------------------------------------------------------------------------
_Release Date: 2024-11-13_

- FAQ Accordion heading behavior.

9.8.6
--------------------------------------------------------------------------------
_Release Date: 2024-11-13_

- Removed the need for allowed_formats module.

9.8.5
--------------------------------------------------------------------------------
_Release Date: 2024-11-13_

- SRC-38: removing the section and div specificity

9.8.4
--------------------------------------------------------------------------------
_Release Date: 2024-11-11_

- Consistently apply person node styles across teasers and lists.

9.8.3
--------------------------------------------------------------------------------
_Release Date: 2024-11-08_

- Clean up decoupled preview url
- CORE-442 Hide description field on menu link content form
- D8CORE-7590 Adjust editori11y settings for intranets
- remove unused css code in publications
- D8CORE-7354 Apply teaser styles on all pages, not just basic pages
- D8CORE-7305 Use article tags only when a header is provided
- D8CORE-6324 Set the left padding on the layout instead of the node page styles
- Restrict FAQ paragraph to single column layouts
- moved margin bottom on news article (#338)
- D8CORE-7446 Scope google analytics cookie to the individual site (#339)

9.8.2
--------------------------------------------------------------------------------
_Release Date: 2024-10-17_

- Escape special characters in title text for html cleanup

9.8.1
--------------------------------------------------------------------------------
_Release Date: 2024-10-16_

- Added created timestamp for site information cron

9.8.0
--------------------------------------------------------------------------------
_Release Date: 2024-10-11_

- SUL23-486 Remove redundant title attribute and disable responsive tables for decoupled Sites
- Cleaned up old code.

9.7.3
--------------------------------------------------------------------------------
_Release Date: 2024-09-24_

- Remove Algolia record when a node is unpublished.

9.7.2
--------------------------------------------------------------------------------
_Release Date: 2024-09-23_

- Decoupled invalidation for node edits if the node is in the menu.

9.7.1
--------------------------------------------------------------------------------
_Release Date: 2024-09-17_

- Fixed site information cron job for canonical url.

9.7.0
--------------------------------------------------------------------------------
_Release Date: 2024-09-16_

- moved relative link url validation to stanford_fields
- Adjust field access to avoid conflicting with node access
- Run tests with Drupal 11 (#325)
- Improve performance for detecting decoupled sites
- Delete algolia records immediately upon entity deletion (#322)
- Adjust xmlsitemap to submit to google with the correct domain
- D8CORE-7455 Algolia Search: Decode html entities before stripping the html (#321)
- Downgrade preact for event minicalendar
- Added decoupled drush command to invalidate path
- Fixed next connect drush command domain default value
- Added escape handler on event mini calendar
- Removed unused page_manager dependencies
- D8CORE-7429 Style event subheadline without h2 tag
- D8CORE-7425 Added dek and date fields for news vertical teaser cards
- Prevent unnecessary decoupled invalidations on local environments
- Replaced deprecated code for D11
- Updated node form styles for Drupal 10.3
- Fixed drush command to make nextjs users active
- fixup for next module entity type creator
- Update drush command to connect nextjs site

9.6.15
--------------------------------------------------------------------------------
_Release Date: 2024-06-18_

- Fixed html validation for events mini calendar.

9.6.13
--------------------------------------------------------------------------------
_Release Date: 2024-06-17_

- Added columnheader role to minicalendar table

9.6.10
--------------------------------------------------------------------------------
_Release Date: 2024-05-28_

- D8CORE-7413 Add space between html tags for search indexing

9.6.9
--------------------------------------------------------------------------------
_Release Date: 2024-05-08_

- Fixed error with focal point in GraphQL response for images.

9.6.8
--------------------------------------------------------------------------------
_Release Date: 2024-04-30_

- Trigger next invalidations for events when the start or end date has recently passed.
- Tweak next path invalidation

9.6.7
--------------------------------------------------------------------------------
_Release Date: 2024-04-10_

- Disable editoria11y on decoupled sites
- Add focal point coordinates to graphql data
- Add "Next" invalidation for referencing entities when a referenced entity changes
- D8CORE-7296 Fix icon alignment on mobile for person detail page;
- D8CORE-7345 Fixed heading spacing on person teasers
- D8CORE-7299 Fine tune intranet algolia indexing (#310)

9.6.6
--------------------------------------------------------------------------------
_Release Date: 2024-03-28_

- Added Redirect support in Graphql.

9.6.5
--------------------------------------------------------------------------------
_Release Date: 2024-03-21_

- D8CORE-7189 Update banner styles

9.6.4
--------------------------------------------------------------------------------
_Release Date: 2024-03-20_

- Improve algolia search indexing primarily for intranet sites.

9.6.3
--------------------------------------------------------------------------------
_Release Date: 2024-03-18_

- Improve algolia data with urls.

9.6.2
--------------------------------------------------------------------------------
_Release Date: 2024-03-18_

- Removed hard coded h3 header in course list pattern

9.6.1
--------------------------------------------------------------------------------
_Release Date: 2024-03-17_

- Allow heading block to be visually hidden in layout builder.


9.6.0
--------------------------------------------------------------------------------
_Release Date: 2024-03-14_

- Clean up Algolia search index data before sending
- D8CORE-7333 Change teaser and list paragraph headline behaviors (#300)
- D8CORE-7307 Move /patterns to /admin/patterns
- D8CORE-7189 Update splash font card headline margin-bottom (#299)
- Added drush command to easily connect a Next.js site
- D8CORE-7212 Add behavior to teaser paragraph to change headers to h3 (#294)
- D8CORE-7208 D8CORE-7209 Prevent page title when using page title banner' (#291)
- D8CORE-7211 D8CORE-7210 Add header behaviors for banner and card paragraphs (#292)
- Updated help text for publishing scheduler (#289)

9.5.1
--------------------------------------------------------------------------------
_Release Date: 2024-02-15_

- Fixed logging syntax.

9.5.0
--------------------------------------------------------------------------------
_Release Date: 2024-02-14_

- Fixed template include path
- Removed repeated templates
- Unlock xmlsitemap rebuild and search api reindexing forms
- D8CORE-4039 Add spacing on events page between the body and components
- Add rel=nofollow to print links on policy pages
- D8CORE-7131 Allow site managers to save config pages with logos uploaded from others
- D8CORE-7103 Push the gallery image under the text on smaller screens
- D8CORE-7086 Add space before news social links
- Fix layout builder blocks not showing all menu options
- D8CORE-5956 Display event types horizontal instead of vertical
- Wrap citation generator in error handling with a user message
- D8CORE-7215 Modify layout builder block for taxonomy menu labels (#290)
- D8CORE-7207 Prevent use of admin html (#288)
- Allow access to layout field for decoupled data
- Implemented decoupled submodule with some JSON API and GraphQL parts (#287)
- Provide some plugins for GraphQL Compose module (#286)

9.4.2
--------------------------------------------------------------------------------
_Release Date: 2023-12-12_

- Added composer conflict for seboettg/collection >= 4.0.0 since it breaks publications.

9.4.1
--------------------------------------------------------------------------------
_Release Date: 2023-12-05_

- D8CORE-7074 Alter search api custom field to support better tokens (#282)
- Added cron job to construct site information file

9.4.0
--------------------------------------------------------------------------------
_Release Date: 2023-10-23_

- New Next Preview plugin for draft mode and automatic entity type creation.

9.3.5
--------------------------------------------------------------------------------
_Release Date: 2023-10-20_

- updating the Decanter version (#278)
- Adjust intranet roles when deleting roles
- Update layout paragraphs node grid (#276)
- D8CORE-6968 Fix gap and update all webpack setups (#275)
- D8CORE-6715 Better hiding of list paragraph (#274)
- removed pipe from past events on node page (#273

9.3.4
--------------------------------------------------------------------------------
_Release Date: 2023-09-25_

- Fixed `spaceless` tag in twig files.

9.3.3
--------------------------------------------------------------------------------
_Release Date: 2023-09-20_

- Fixed node form layout for D10

9.3.2
--------------------------------------------------------------------------------
_Release Date: 2023-09-11_

- Allow some necessary blocks to be public on intranets

9.3.1
--------------------------------------------------------------------------------
_Release Date: 2023-09-08_

- Update unit tests for d10 (#268)
- Spacer paragraph - changed label of default value

9.3.0
--------------------------------------------------------------------------------
_Release Date: 2023-09-07_

- Add response_code_condition replacing the contrib module (#266)
- D8CORE-6802 Update preview styles with most of the front end styles (#262)
- D8CORE-4495 Update past events text on node page

9.2.15
--------------------------------------------------------------------------------
_Release Date: 2023-08-04_
- 69c5073 SDSS-763: Check for number of regions on banner restriction. (#261)

9.2.14
--------------------------------------------------------------------------------
_Release Date: 2023-07-28_
- Update event subscribers to match interface declaration

9.2.13
--------------------------------------------------------------------------------
_Release Date: 2023-07-25_
- D8CORE-6838 fix responsive two column layout
- 100% image in cards
- D8CORE-6791 Remove access to sitemap.xml for intranet sites

9.2.12
--------------------------------------------------------------------------------
_Release Date: 2023-07-18_

- D8CORE-6820 Increased z-index for ckeditor balloons (link dialogs)

9.2.11
--------------------------------------------------------------------------------
_Release Date: 2023-07-07_

- Specific styles for full width basic pages.

9.2.10
--------------------------------------------------------------------------------
_Release Date: 2023-07-07_

- reverted language change for layout paragraphs
- Handle the action link behavior better
- Prevent gallery from 2 and 3 column layouts
- D8CORE-6800 D8CORE-6803 fix gutters on rows and remove token link on list paragraph field

9.2.9
--------------------------------------------------------------------------------
_Release Date: 2023-06-29_

- adding the underline to splash in theme (#253)
- added button and link styles to the layout paragraph preview (#251)
- D8CORE-4850 add space between people rows after load more
- D8CORE-2005 Refactor section to div tag for people page sections
- D8CORE-6463 Prevent encoded subject names in course listing
- Margin 0 on the paragraph icons for layout paragraphs
- Various tweaks to layout paragraphs form displays (#252)
- D8CORE-6541 Index home page for search
- HSD8-6476 Increase authority field for policy
- D8CORE-6758 | Fixup gallery h2 for layout paragraphs (#249)
- added webpack scss compiler
- added max width for paragraphs on large screens
- Set max width for media captions
- Fixed some styles for responsive paragraphs
- Corrected gallery grid display
- alter nobots field widget with state default value
- D8CORE-4495 Update past events list styles (#248)
- Adjusted styles for the list paragraph in the new layout paragraphs
- Revert "changing lineheight of past event note (#245)" (#247)
- changing lineheight of past event note (#245)


9.2.8
--------------------------------------------------------------------------------
_Release Date: 2023-06-23_

- Restored link processing for card paragraph.

9.2.7
--------------------------------------------------------------------------------
_Release Date: 2023-06-12_

- Use a better github action for releases
- Set minimum height to Layout Builder component
- Additional check for route name
- Fixed styles for cards on giant screen full width
- Fixed styles for two column different widths
- Fixed error when page_cache_query_ignore module is disabled

9.2.6
--------------------------------------------------------------------------------
_Release Date: 2023-05-17_

- Fixed AJAX issue on taxonomy form related to the argument helper.

9.2.5
--------------------------------------------------------------------------------
_Release Date: 2023-05-15_

- Base64 json api processor for image urls.
- Added styles for paragraphs and layout paragraphs.

9.2.4
--------------------------------------------------------------------------------
_Release Date: 2023-05-12_

- Provide some hooks to improve or fix parts when the site is inteneded to be decoupled.

9.2.3
--------------------------------------------------------------------------------
_Release Date: 2023-05-10_

- Fixed error when editing taxonomy.

9.2.2
--------------------------------------------------------------------------------
_Release Date: 2023-05-10_

- Fixed intranet background not stretching.
- Added to taxonomy form to display the argument format of the term name.

9.2.1
--------------------------------------------------------------------------------
_Release Date: 2023-05-05_

- Fixed bug when saving menu items.
- Added support to clear caches when saving the menu link field module.

9.2.0
--------------------------------------------------------------------------------
_Release Date: 2023-04-27_

- NEW layout paragraphs enhancer module for LP layouts and styles.
- Removed all deprecated code for D10 Prep.
- Move card link field to paragraph behaviors.
-

9.1.0
--------------------------------------------------------------------------------
_Release Date: 2023-01-17_

9.0.8
--------------------------------------------------------------------------------
_Release Date: 2023-01-17_

- Fixed bug by checking for node type before setting policy prefix.

9.0.7
--------------------------------------------------------------------------------
_Release Date: 2022-12-13_

- Refactored page_cache_query_ignore config override

9.0.6
--------------------------------------------------------------------------------
_Release Date: 2022-11-29_

- Adjusted page cache query ignore override settings.

9.0.5
--------------------------------------------------------------------------------
_Release Date: 2022-11-29_

- Added overflow-wrap property to event location

9.0.4
--------------------------------------------------------------------------------
_Release Date: 2022-11-21_

- D8CORE-6422 Hide social share icons if configured on the news content

9.0.3
--------------------------------------------------------------------------------
_Release Date: 2022-11-11_

- Override page_cache_query_ignore settings with view queries (#219)
- removing extra spacing on people unstyled lists (#217)
- Prevented spaces from being stripped from course code (#218)

9.0.2
--------------------------------------------------------------------------------
_Release Date: 2022-10-19_

- fix to font size on people lists.

9.0.1
--------------------------------------------------------------------------------
_Release Date: 2022-10-17_

- Update hook to delete imported courses.

9.0.0
--------------------------------------------------------------------------------
_Release Date: 2022-10-17_

- Revived the need for this package
- Consolidated all submodules from `stanford_profile` into the modules subdir.

8.2.4
--------------------------------------------------------------------------------
_Release Date: 2022-07-08_

- fixed composer namespace to lowercase
- D8CORE-5598 making margins even (#209)
- Merge branch 'master' into 8.x

8.2.3
--------------------------------------------------------------------------------
_Release Date: 2022-05-24_

- Adjusted VBO form for event date fields that are required
- fixup for the transparent hover

8.x-2.2
--------------------------------------------------------------------------------
_Release Date: 2022-05-10_

- Added conditional to fix null fatal error on 8102 update. (#205)

8.x-2.1
--------------------------------------------------------------------------------
_Release Date: 2022-05-09_

- Update hook and drush command to move all public files into private file system. (#202)
- set up the footer text to inherit the smaller size (#201)

8.x-2.0
--------------------------------------------------------------------------------
_Release Date: 2022-05-02_

- Updated dev dependencies (#199)
- D8CORE-5750 Modify field render arrays to better suite the DS module limits (#198)
- D8CORE-5686 Adjusted scheduler help text (#197)
- adding unstyle list to the term list (#196)
- People term list style fixes (#194)
- limited stripes to tbody only (#195)
- utility classes for tables (#192)
- updating styles for shared tags (#193)
- updating decanter (#191)
- removing the image when in 2-col (#188)
- Improved cache tags for views when no filters are available
- D8CORE-4128 Adjusted styles to change views to HTML lists (#190)
- Drush command to easily set up intranets (#189)
- D8CORE-5615 Styles for shared tags view (#187)


8.x-1.34
--------------------------------------------------------------------------------
_Release Date: 2022-03-17_

- Changed order of module & theme enabling
- Enable minimally branded theme for easier switching (#185)
- D8CORE-3345 Update hook to update paths for terms and content. (#183)
- D8CORE-5574 D8CORE-5575 D8CORE-5576 Adjustments to the schedule module form displays (#184)
- D8CORE-5583 Improve menu tree cache tags (#179)
- Switch to conditional fields instead of form alter (#182)


8.x-1.33
--------------------------------------------------------------------------------
_Release Date: 2022-03-08_

- Check the local footer 2nd cell is an array we can manipulate
- D8CORE-4974 Process the local footer to wrap the second and thrid local footer contents (#175)
- BOT-8: Add intranet state to allow file uploads (#176)
- D8CORE-5278 Removed unwanted menu links from scheduler (#174)


8.x-1.32
--------------------------------------------------------------------------------
_Release Date: 2022-02-03_

- hot fix adding back padding for mobile view (#171)


8.x-1.31
--------------------------------------------------------------------------------
_Release Date: 2022-01-27_

- Purge the source url when a redirect is saved (#169)
- Modify the imported redirects to point at the node instead of the alias (#168)
- Check if the form as VBO before sorting actions (#167)
- fixes to the after in a heading for the new localist work (#166)
- fixing the spacing between basic cards when in a list (#165)
- Added sorting to the node actions (#159)
- updated tests for D9.3 (#164)


8.x-1.30
--------------------------------------------------------------------------------
_Release Date: 2021-12-03_

- visually hid external link svgs in localist embeddable. (#162)


8.x-1.29
--------------------------------------------------------------------------------
_Release Date: 2021-12-01_

- working on card margin bottoms when stacked. (#158)


8.x-1.28
--------------------------------------------------------------------------------
_Release Date: 2021-11-19_

- Increase the module weight to take more priority over other modules
- D8CORE-4246 unset stanford_basic FA library if the FA module exists (#155)
- updating external link icon on headings (#154)
- D8CORE-4548 Prevent fatal error when menu items arent routed during save
- Convert url object into a string for better rendering (#156)
-  D8CORE-4878 Updated hook to work with recent config changes.  (#153)
- removing the extra space about the button in lists (#152)


8.x-1.27
--------------------------------------------------------------------------------
_Release Date: 2021-10-21_

- Hotfix: do not require event date and time in bulk edit form
- Ignore home, 404, and 403 pages from indexing (#149)
- Merge branch 'master' into 8.x-1.x

8.x-1.26
--------------------------------------------------------------------------------
_Release Date: 2021-10-11_

- removing bold change

8.x-1.25
--------------------------------------------------------------------------------
_Release Date: 2021-10-08_

- Allow paragraphs to be indexed when intranet is enabled (#146)
- D8CORE-4679 Add site improve analytics js (#145)
- adding bold to the external links (#139)
- D8CORE-4793 Configure mathjax to only target equations in <p> tags (#144)
- D8CORE-4759 Clear search results cache after a node saves
- Update hook to add role for users with custom LB content (#142)
- Added check to make sure field exists before adding constraint (#143)


8.x-1.24
--------------------------------------------------------------------------------
_Release Date: 2021-09-07_

- D8CORE-4733 Allow file downloads for fields from config pages (#138)


8.x-1.23
--------------------------------------------------------------------------------
_Release Date: 2021-09-03

- correcting the line contrast for the intranet wysiwyg table (#137)

8.x-1.22
--------------------------------------------------------------------------------
_Release Date: 2021-08-11_

- D8CORE-4696 Fix the access for private images that were converted to png files via styles (#131)
- D8CORE-4690 Modify mathjax filter plugin to fix media and spacing issues (#133)
- Unset mathjax setup library using a hook due to ajax

8.x-1.21
--------------------------------------------------------------------------------
_Release Date: 2021-07-19_

- fixing the date alignment for an event list (#129) (3eb1809)

8.x-1.20
--------------------------------------------------------------------------------
_Release Date: 2021-07-09_

- Disable a mathjax library.

8.x-1.19
--------------------------------------------------------------------------------
_Release Date: 2021-07-09_

- clearing the floats if used in the wysiwyg for the medium and down breakpoint (#125) (c98060a)

8.x-1.18
--------------------------------------------------------------------------------
_Release Date: 2021-06-16_

- Corrected colorbox library dependency definition.

8.x-1.17
--------------------------------------------------------------------------------
_Release Date: 2021-06-15_

- Corrected grid styles for news cards in rows.

8.x-1.16
--------------------------------------------------------------------------------
_Release Date: 2021-06-11_

- Disable the grid style for people lists in rows (#118) (ac970c5)
- D8CORE-3566 Invalidate cache when saving config pages (e2032ec)
- Fixing the h2 that wasn't wrapping (#114) (eb9652f)
- D8CORE-4104 Improve colorbox with javascript (#113) (60b8208)
- D8CORE-2028 Change "People" to "Users" in the admin toolbar (#110) (6f9a64a)

8.x-1.15
--------------------------------------------------------------------------------
_Release Date: 2021-05-07_

- adding teaser styles for event series (#106) (a40d5fb)
- Fixup the specificity problem for the local footer color (#105) (b07bdf9)

8.x-1.14
--------------------------------------------------------------------------------
_Release Date: 2021-04-19_

- fixing up the padding to be on left and right (#103) (3949fe5)
- D8CORE-4115 Remove the "Unlock" button on node form (#102) (274bee7)
- Basic pages+ styling fix (#101) (453befa)

8.x-1.13
--------------------------------------------------------------------------------
_Release Date: 2021-04-12_

- Add preprocess to display only one basic page image in the card.

8.x-1.12
--------------------------------------------------------------------------------
_Release Date: 2021-04-09_

- basic page plus functionality (#90) (2cc9e5a)
- adding constraint for global message validation (#88) (11d09a9)
- Intranet styles. (#89) (54bd930)
- Styles for publication teaser displays (#96) (2f89062)
- Removed dropcap pseudo content: so that dropcap displays (#95) (4b01951)
- D8CORE-2853 Prevent unpublishing home, 403, or 404 pages (#93) (12dd3c7)
- D8CORE-4021 Give site managers permission to assign custom roles (#91) (f91a648)
- visually hide duration dropdown in smartdate fields (#85) (0b5d1d4)
- D8CORE-3126 intranet functionality (#86) (267aa53)

8.x-1.11
--------------------------------------------------------------------------------
_Release Date: 2021-03-17_

- Adjusted event card style widths.

8.x-1.10
--------------------------------------------------------------------------------
_Release Date: 2021-03-09_

- Allow only 3 items per row on basic pages.

8.x-1.9
--------------------------------------------------------------------------------
_Release Date: 2021-03-05_

- fixing the caption styling on the gallery. (#80) (30fbadf)
- Template and styles for search results (#78) (47e0670)
- Updated admin toolbar module (#79) (3042933)
- D8CORE-3520 All Publications list page (#74) (322432b)
- D8CORE-3476 Create a new view display mode specific for viewfields (#77) (3ad05e3)
- D8CORE-3564 Dont allow the new pub type view in the view paragraph type (#76) (dc18d1b)
- fixing the missing arrow icon (#75) (d92a762)
- Added update hook to enable the stable9 theme (58e0ac3)

8.x-1.8
--------------------------------------------------------------------------------
_Release Date: 2021-02-08_

- D8CORE-3438 Adjusted styles for full width page gallery items (#71) (8fc9196)
- Set config entity uuids when they are created if they dont match (#70) (edfd548)
- limit the publication views in the viewfield (#68) (2ecf612)
- adding a little space on lists in columns (#69) (602fadb)
- fixing news list alignment. (#64) (92d7d2a)
- fixing margin bottom on lists as cards (#65) (2aa14f2)
- fixed react paragraphs library name (a9c3ab5)
- Fixed ckeditor styles path (#67) (298c1ce)
- D8CORE-3263 Gallery paragraph styling & better scss file structure (#62) (7cb4749)
- Centering the title and button for teasers. (#63) (8a2ac19)
- Styling some padding for the su-intro (#60) (a5b109e)
- Updated circleci testing (#59) (e890c66)
- Updated field_formatter_class module (9e02372)
- menu underline fix (#55) (2a9d914)

8.x-1.7
--------------------------------------------------------------------------------
_Release Date: 2020-12-04_

- removing the character limits on the wysiwyg text field (#44) (3efdc37)
- D8CORE-2668 Removed title attribute from taxonomy menu items (#54) (68baf99)
- D9 Ready (#50) (aab94a8)
- removing action link and changing colors for mailto links (#51) (1bd15eb)
- Change the github actions to tag PRs on master (#49) (6bfeb4c)

8.x-1.6
--------------------------------------------------------------------------------
_Release Date: 2020-11-06_

- Reset react tools to a basic array (29a9bfa)
- D8CORE-2951 D8CORE-2952 Style adjustments for people cards (#46) (00764d9)
- D8CORE-2570 Update hook to create the intro blocks (#45) (04b0413)
- V1.5.0 tweaks (#43) (78f37af)
- D8core-2765 Styles and icons for mailto buttons (#39) (b84b521)
- D8CORE-000 List and content reference paragraph style helpers (#42) (9c386a5)
- D8CORE-2183 Keep the relations fieldset open by default on term form (#41) (24dd121)
- D8CORE-2780 People card images are circles (#40) (56fe706)
- adding the css to the pages that need the ckeditor style… (#38) (a6a416c)
- Adjusted the list paragraph styles for grid displays (#37) (3a9c06a)
- D8CORE-2738 Reduce list paragraph max width (#36) (dcb9a46)
- Limit paragraph choices for now (#35) (73efcc5)
- D8CORE-2856 Remove unwanted views from the list paragraph type (#34) (96203a3)
- D8CORE-000 Fixed breaking if the parent menu item is external (#33) (ba7d3b8)

8.x-1.5
--------------------------------------------------------------------------------
_Release Date: 2020-10-12_

- D8CORE-2184 Adjust taxonomy terms to only 1 parent (#30)

8.x-1.4
--------------------------------------------------------------------------------
_Release Date: 2020-10-05_

- Delete taxonomy menu links when the parents change (#28) (98aa957)
- Added additional help text and snow form links (#27) (2be1dee)
- Added presave to remove all field permissions from field storage that arent needed (#26) (3c0dca8)

8.x-1.2
--------------------------------------------------------------------------------
_Release Date: 2020-09-14_

- Add hook for field permission on open embed field. (#23) (95d6fac)
- Name key changes. (#21) (82c0791)
- Rebuild router on taxonomy change to fix bug with taxonomy_menu. (#20) (d309423)
- Super Footer and Global Messages (#15) (7e22801)
- external link adjustments (#16) (803b124)
- Update styles for react paragraphs V2 (#18) (cd5c1ae)
- D8CORE 2541 Added padding to images in the wysiwyg paragraph type (#19) (527e33e)
- D8CORE-2499 Updated composer license (#17) (c04aa91)
- D8CORE-2201, External link module styles. (#11) (b720bb2)
- fixing the margins on the full width page (#14) (75d6f57)

8.x-1.1
--------------------------------------------------------------------------------
_Release Date: 2020-08-07_

- setting max-width to 980 like the cards (#12) (3d0424f)
- Config pages lockup Form helpers (#10) (e7b662f)
- CSD-258: Hide checkbox that could cause damage. (#8) (8678198)
- added comment (be4af9f)
- fixup (2740425)
- CSD-258 Hide checkbox that could cause damage (f6784c1)

8.x-1.0-rc1
--------------------------------------------------------------------------------
_Release Date: 2020-07-13_

- Initial release
