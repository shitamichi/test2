<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

/**
 * User モデルの属性定義に関する軽量な確認テスト。
 *
 * DBにもフレームワーク起動にも依存しないため Unit スイートに置いている。
 * （CIのランナーには本番と同じ SQL Server が無く、DB依存のテストは流せない）
 */
class UserTest extends TestCase
{
    /**
     * 一括代入を許可する属性が、意図した3つだけであること。
     *
     * #[Fillable] に項目が増えると、リクエストの値をそのまま保存できる
     * 範囲が広がる。意図しない追加に気づくための確認。
     */
    public function test_fillable attributes_are_limited_to_expected_columns(): void
    {
        $user = new User;

        $this->assertSame(['name', 'email', 'password'], $user->getFillable());
    }

    /**
     * 配列・JSON化の際に隠す属性へ password と remember_token が
     * 含まれていること。
     *
     * ここが外れるとAPIレスポンスやログにハッシュ値が出るため、
     * 情報漏洩を防ぐうえで重要な確認。
     */
    public function test_password_and_remember_token_are_hidden(): void
    {
        $user = new User;

        $this->assertContains('password', $user->getHidden());
        $this->assertContains('remember_token', $user->getHidden());
    }

    /**
     * キャスト定義が意図どおりであること。
     *
     * password の 'hashed' が外れると平文のまま保存される事故につながる。
     */
    public function test_casts_are_configured_as_expected(): void
    {
        $casts = (new User)->getCasts();

        $this->assertSame('datetime', $casts['email_verified_at'] ?? null);
        $this->assertSame('hashed', $casts['password'] ?? null);
    }

    /**
     * toArray() の結果に hidden 指定の属性が出ないこと。
     *
     * setRawAttributes() を使うのは、通常の代入だと password の 'hashed'
     * キャストが走って Hash ファサード（＝アプリ起動）が必要になるため。
     * ここはフレームワーク非依存に保ちたい。
     */
    public function test_hidden_attributes_are_excluded_from_array(): void
    {
        $user = (new User)->setRawAttributes([
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'dummy-hash',
            'remember_token' => 'dummy-token',
        ]);

        $array = $user->toArray();

        $this->assertSame('テスト太郎', $array['name']);
        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
    }
}
