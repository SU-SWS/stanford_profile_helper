# Stanford Profile Helper

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

- D8CORE-6976: updating the Decanter version (#278)
- Adjust intranet roles when deleting roles
- Update layout paragraphs node grid (#276)
- D8CORE-6968 Fix gap and update all webpack setups (#275)
- D8CORE-6715 Better hiding of list paragraph (#274)
- D8CORE-5952: removed pipe from past events on node page (#273

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
- D8CORE-6824: Spacer paragraph - changed label of default value

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

- D8CORE-4778: adding the underline to splash in theme (#253)
- D8CORE-6761: added button and link styles to the layout paragraph preview (#251)
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
- Revert "D8CORE-4495: changing lineheight of past event note (#245)" (#247)
- D8CORE-4495: changing lineheight of past event note (#245)


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

- D8CORE-3542: Added overflow-wrap property to event location

9.0.4
--------------------------------------------------------------------------------
_Release Date: 2022-11-21_

- D8CORE-6422 Hide social share icons if configured on the news content

9.0.3
--------------------------------------------------------------------------------
_Release Date: 2022-11-11_

- Override page_cache_query_ignore settings with view queries (#219)
- D8CORE-2932: removing extra spacing on people unstyled lists (#217)
- D8CORE-5865: Prevented spaces from being stripped from course code (#218)

9.0.2
--------------------------------------------------------------------------------
_Release Date: 2022-10-19_

- D8CORE-6344: fix to font size on people lists.

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
- D8CORE-5598 D8CORE-5592: making margins even (#209)
- Merge branch 'master' into 8.x

8.2.3
--------------------------------------------------------------------------------
_Release Date: 2022-05-24_

- Adjusted VBO form for event date fields that are required
- D8CORE-5837: fixup for the transparent hover

8.x-2.2
--------------------------------------------------------------------------------
_Release Date: 2022-05-10_

- Added conditional to fix null fatal error on 8102 update. (#205)

8.x-2.1
--------------------------------------------------------------------------------
_Release Date: 2022-05-09_

- Update hook and drush command to move all public files into private file system. (#202)
- D8CORE-5829: set up the footer text to inherit the smaller size (#201)

8.x-2.0
--------------------------------------------------------------------------------
_Release Date: 2022-05-02_

- Updated dev dependencies (#199)
- D8CORE-5750 Modify field render arrays to better suite the DS module limits (#198)
- D8CORE-5686 Adjusted scheduler help text (#197)
- D8CORE-5748: adding unstyle list to the term list (#196)
- D8CORE-5729: People term list style fixes (#194)
- D8CORE-5771: limited stripes to tbody only (#195)
- D8CORE-5567: utility classes for tables (#192)
- D8CORE-5749: updating styles for shared tags (#193)
- D8CORE-4728: updating decanter (#191)
- D8CORE-4842: removing the image when in 2-col (#188)
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

- D8CORE-5418: hot fix adding back padding for mobile view (#171)


8.x-1.31
--------------------------------------------------------------------------------
_Release Date: 2022-01-27_

- Purge the source url when a redirect is saved (#169)
- Modify the imported redirects to point at the node instead of the alias (#168)
- Check if the form as VBO before sorting actions (#167)
- D8CORE-5183: fixes to the after in a heading for the new localist work (#166)
- D8CORE-5106: fixing the spacing between basic cards when in a list (#165)
- D8CORE-4833: Added sorting to the node actions (#159)
- updated tests for D9.3 (#164)


8.x-1.30
--------------------------------------------------------------------------------
_Release Date: 2021-12-03_

- D8CORE-5096: visually hid external link svgs in localist embeddable. (#162)


8.x-1.29
--------------------------------------------------------------------------------
_Release Date: 2021-12-01_

- D8CORE-5110: working on card margin bottoms when stacked. (#158)


8.x-1.28
--------------------------------------------------------------------------------
_Release Date: 2021-11-19_

- Increase the module weight to take more priority over other modules
- D8CORE-4246 unset stanford_basic FA library if the FA module exists (#155)
- D8CORE-4843: updating external link icon on headings (#154)
- D8CORE-4548 Prevent fatal error when menu items arent routed during save
- Convert url object into a string for better rendering (#156)
-  D8CORE-4878 Updated hook to work with recent config changes.  (#153)
- D8CORE-3166: removing the extra space about the button in lists (#152)


8.x-1.27
--------------------------------------------------------------------------------
_Release Date: 2021-10-21_

- Hotfix: do not require event date and time in bulk edit form
- Ignore home, 404, and 403 pages from indexing (#149)
- Merge branch 'master' into 8.x-1.x

8.x-1.26
--------------------------------------------------------------------------------
_Release Date: 2021-10-11_

- D8CORE-4859: removing bold change

8.x-1.25
--------------------------------------------------------------------------------
_Release Date: 2021-10-08_

- Allow paragraphs to be indexed when intranet is enabled (#146)
- D8CORE-4679 Add site improve analytics js (#145)
- D8CORE-4691: adding bold to the external links (#139)
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

- D8CORE-4098: correcting the line contrast for the intranet wysiwyg table (#137)

8.x-1.22
--------------------------------------------------------------------------------
_Release Date: 2021-08-11_

- D8CORE-4696 Fix the access for private images that were converted to png files via styles (#131)
- D8CORE-4690 Modify mathjax filter plugin to fix media and spacing issues (#133)
- Unset mathjax setup library using a hook due to ajax

8.x-1.21
--------------------------------------------------------------------------------
_Release Date: 2021-07-19_

- D8CORE-4536: fixing the date alignment for an event list (#129) (3eb1809)

8.x-1.20
--------------------------------------------------------------------------------
_Release Date: 2021-07-09_

- Disable a mathjax library.

8.x-1.19
--------------------------------------------------------------------------------
_Release Date: 2021-07-09_

- D8CORE-4225: clearing the floats if used in the wysiwyg for the medium and down breakpoint (#125) (c98060a)

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

- D8CORE-4341: Disable the grid style for people lists in rows (#118) (ac970c5)
- D8CORE-3566 Invalidate cache when saving config pages (e2032ec)
- D8CORE-4178: Fixing the h2 that wasn't wrapping (#114) (eb9652f)
- D8CORE-4104 Improve colorbox with javascript (#113) (60b8208)
- D8CORE-2028 Change "People" to "Users" in the admin toolbar (#110) (6f9a64a)

8.x-1.15
--------------------------------------------------------------------------------
_Release Date: 2021-05-07_

- D8CORE-3970: adding teaser styles for event series (#106) (a40d5fb)
- D8CORE-4097: Fixup the specificity problem for the local footer color (#105) (b07bdf9)

8.x-1.14
--------------------------------------------------------------------------------
_Release Date: 2021-04-19_

- D8CORE-4092: fixing up the padding to be on left and right (#103) (3949fe5)
- D8CORE-4115 Remove the "Unlock" button on node form (#102) (274bee7)
- Basic pages+ styling fix (#101) (453befa)

8.x-1.13
--------------------------------------------------------------------------------
_Release Date: 2021-04-12_

- Add preprocess to display only one basic page image in the card.

8.x-1.12
--------------------------------------------------------------------------------
_Release Date: 2021-04-09_

- D8CORE-3254: basic page plus functionality (#90) (2cc9e5a)
- D8CORE-2766: adding constraint for global message validation (#88) (11d09a9)
- D8CORE-3686: Intranet styles. (#89) (54bd930)
- D8CORE-4020: Styles for publication teaser displays (#96) (2f89062)
- D8CORE-2942: Removed dropcap pseudo content: so that dropcap displays (#95) (4b01951)
- D8CORE-2853 Prevent unpublishing home, 403, or 404 pages (#93) (12dd3c7)
- D8CORE-4021 Give site managers permission to assign custom roles (#91) (f91a648)
- D8CORE-2600: visually hide duration dropdown in smartdate fields (#85) (0b5d1d4)
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

- D8CORE-3540: fixing the caption styling on the gallery. (#80) (30fbadf)
- Template and styles for search results (#78) (47e0670)
- Updated admin toolbar module (#79) (3042933)
- D8CORE-3520 All Publications list page (#74) (322432b)
- D8CORE-3476 Create a new view display mode specific for viewfields (#77) (3ad05e3)
- D8CORE-3564 Dont allow the new pub type view in the view paragraph type (#76) (dc18d1b)
- D8CORE-3516: fixing the missing arrow icon (#75) (d92a762)
- Added update hook to enable the stable9 theme (58e0ac3)

8.x-1.8
--------------------------------------------------------------------------------
_Release Date: 2021-02-08_

- D8CORE-3438 Adjusted styles for full width page gallery items (#71) (8fc9196)
- Set config entity uuids when they are created if they dont match (#70) (edfd548)
- limit the publication views in the viewfield (#68) (2ecf612)
- D8CORE-3163: adding a little space on lists in columns (#69) (602fadb)
- D8CORE-3164: fixing news list alignment. (#64) (92d7d2a)
- D8CORE-3053: fixing margin bottom on lists as cards (#65) (2aa14f2)
- fixed react paragraphs library name (a9c3ab5)
- Fixed ckeditor styles path (#67) (298c1ce)
- D8CORE-3263 Gallery paragraph styling & better scss file structure (#62) (7cb4749)
- D8CORE-3052: Centering the title and button for teasers. (#63) (8a2ac19)
- D8CORE-3142: Styling some padding for the su-intro (#60) (a5b109e)
- Updated circleci testing (#59) (e890c66)
- Updated field_formatter_class module (9e02372)
- D8CORE-2764: menu underline fix (#55) (2a9d914)

8.x-1.7
--------------------------------------------------------------------------------
_Release Date: 2020-12-04_

- D8CORE-2899: removing the character limits on the wysiwyg text field (#44) (3efdc37)
- D8CORE-2668 Removed title attribute from taxonomy menu items (#54) (68baf99)
- D9 Ready (#50) (aab94a8)
- D8CORE-2765: removing action link and changing colors for mailto links (#51) (1bd15eb)
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
- D8CORE-2285: adding the css to the pages that need the ckeditor style… (#38) (a6a416c)
- Adjusted the list paragraph styles for grid displays (#37) (3a9c06a)
- D8CORE-2738 Reduce list paragraph max width (#36) (dcb9a46)
- D8CORE-2875: Limit paragraph choices for now (#35) (73efcc5)
- D8CORE-2856 Remove unwanted views from the list paragraph type (#34) (96203a3)
- D8CORE-000 Fixed breaking if the parent menu item is external (#33) (ba7d3b8)

8.x-1.5
--------------------------------------------------------------------------------
_Release Date: 2020-10-12_

- D8CORE-2184 Adjust taxonomy terms to only 1 parent (#30)

8.x-1.4
--------------------------------------------------------------------------------
_Release Date: 2020-10-05_

- D8CORE-2613: Delete taxonomy menu links when the parents change (#28) (98aa957)
- D8CORE-2644: Added additional help text and snow form links (#27) (2be1dee)
- Added presave to remove all field permissions from field storage that arent needed (#26) (3c0dca8)

8.x-1.2
--------------------------------------------------------------------------------
_Release Date: 2020-09-14_

- D8CORE-2521: Add hook for field permission on open embed field. (#23) (95d6fac)
- D8CORE-2490: Name key changes. (#21) (82c0791)
- D8CORE-2420: Rebuild router on taxonomy change to fix bug with taxonomy_menu. (#20) (d309423)
- D8CORE-1609: Super Footer and Global Messages (#15) (7e22801)
- D8CORE-2533: external link adjustments (#16) (803b124)
- D8CORE-2040: Update styles for react paragraphs V2 (#18) (cd5c1ae)
- D8CORE 2541 Added padding to images in the wysiwyg paragraph type (#19) (527e33e)
- D8CORE-2499 Updated composer license (#17) (c04aa91)
- D8CORE-2201, D8CORE-2448: External link module styles. (#11) (b720bb2)
- D8CORE-2322: fixing the margins on the full width page (#14) (75d6f57)

8.x-1.1
--------------------------------------------------------------------------------
_Release Date: 2020-08-07_

- D8CORE-2497: setting max-width to 980 like the cards (#12) (3d0424f)
- D8CORE-1472: Config pages lockup Form helpers (#10) (e7b662f)
- CSD-258: Hide checkbox that could cause damage. (#8) (8678198)
- added comment (be4af9f)
- fixup (2740425)
- CSD-258 Hide checkbox that could cause damage (f6784c1)

8.x-1.0-rc1
--------------------------------------------------------------------------------
_Release Date: 2020-07-13_

- Initial release
