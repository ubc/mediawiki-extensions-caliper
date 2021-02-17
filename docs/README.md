

## Caliper Events


### `onBeforePageDisplay`

Hooks: `BeforePageDisplay`

WikiPage Example: [BeforePageDisplayWikiPage.json](examples/BeforePageDisplayWikiPage.json)

Non-WikiPage Example: [BeforePageDisplayNonWikiPage.json](examples/BeforePageDisplayNonWikiPage.json)

Captures page views.


### `onUserLoginComplete`

Hooks: `UserLoginComplete`

Example: [UserLoginComplete.json](examples/UserLoginComplete.json)

Captures user login event.


### `onUserLogout`

Hooks: `UserLogout`

Example: [UserLogout.json](examples/UserLogout.json)

Captures user logout event.


### `onPageContentSaveComplete`

Hooks: `PageContentSaveComplete`

Create WikiPage Example: [PageContentSaveCompleteCreate.json](examples/PageContentSaveCompleteCreate.json)

Create WikiPage Tool Use: [PageContentSaveCompleteCreateToolUse.json](examples/PageContentSaveCompleteCreateToolUse.json)

Edit WikiPage Example: [PageContentSaveCompleteEdit.json](examples/PageContentSaveCompleteEdit.json)

Edit WikiPage Tool Use: [PageContentSaveCompleteEditToolUse.json](examples/PageContentSaveCompleteEditToolUse.json)

Captures WikiPage create and edit events.


### `onArticleDelete`

Hooks: `ArticleDelete`

Example: [ArticleDelete.json](examples/ArticleDelete.json)

Tool Use: [ArticleDeleteToolUse.json](examples/ArticleDeleteToolUse.json)

Captures WikiPage delete event.


### `onArticleUndelete`

Hooks: `ArticleUndelete`

Example: [ArticleUndelete.json](examples/ArticleUndelete.json)

Tool Use: [ArticleUndeleteToolUse.json](examples/ArticleUndeleteToolUse.json)

Captures WikiPage undelete event.


### `onArticleProtectComplete`

Hooks: `ArticleProtectComplete`

Example: [ArticleProtectComplete.json](examples/ArticleProtectComplete.json)

Captures special WikiPage edit event (write protection change).


### `onTitleMoveComplete`

Hooks: `TitleMoveComplete`

Edit Example: [TitleMoveComplete.json](examples/TitleMoveComplete.json)

Create Redirect Example: [TitleMoveCompleteCreateRedirect.json](examples/TitleMoveCompleteCreateRedirect.json)

Captures special WikiPage edit event of changing the title. This event can potentially also create a new redirect WikiPage.


### `onArticleMergeComplete`

Hooks: `ArticleMergeComplete`

Source Example: [ArticleMergeCompleteSource.json](examples/ArticleMergeCompleteSource.json)

Destination Example: [ArticleMergeCompleteDestination.json](examples/ArticleMergeCompleteDestination.json)

Captures the merge history events as edits to both source and destination.


### `onArticleRevisionVisibilitySet`

Hooks: `ArticleRevisionVisibilitySet`

Delete Revision Example: [ArticleRevisionVisibilitySetDelete.json](examples/ArticleRevisionVisibilitySetDelete.json)

Restore Revision Example: [ArticleRevisionVisibilitySetRestore.json](examples/ArticleRevisionVisibilitySetRestore.json)

Captures special WikiPage edit event for soft deleting and restoring revisions.


### `onWatchArticleComplete`

Hooks: `WatchArticleComplete`

Example: [WatchArticleComplete.json](examples/WatchArticleComplete.json)

Captures subscribe to WikiPage changes event.


### `onUnwatchArticleComplete`

Hooks: `UnwatchArticleComplete`

Example: [UnwatchArticleComplete.json](examples/UnwatchArticleComplete.json)

Captures unsubscribe to WikiPage changes event.


### `onMarkPatrolledComplete`

Hooks: `MarkPatrolledComplete`

Example: [MarkPatrolledComplete.json](examples/MarkPatrolledComplete.json)

Captures reviewing and approval of revision changes.


