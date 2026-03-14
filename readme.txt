=== AI Review ===
Contributors: kaibadash
Tags: ai, review, editor, gutenberg, llm
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AIを使って記事を修正するプラグインです。

== Description ==

AI Reviewは、WordPress投稿編集画面のサイドバーからAIによる記事修正を実行できるプラグインです。

* 投稿編集画面の右サイドバーにAI Reviewパネルを表示
* プロンプトを入力して実行ボタンを押すと、AIが記事を修正
* LLMプロバイダ、モデル、APIキー、システムプロンプトを設定画面で管理
* OpenAI互換のChat Completions APIに対応

== Installation ==

1. プラグインをWordPressの`wp-content/plugins/`ディレクトリにアップロード
2. WordPress管理画面の「プラグイン」ページでプラグインを有効化
3. 「設定」→「AI Review」からLLMの設定を行う
4. 投稿編集画面の右サイドバーから「AI Review」パネルを開いて使用

== Changelog ==

= 1.0.0 =
* 初回リリース
* AIによる記事修正機能
* LLM設定管理画面
* 上書き注意書き表示
