<?php

namespace Tests\Feature;

use App\Models\Debt;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateDebtsInCatalanTest extends TestCase
{

    use RefreshDatabase;

    /** @test */
    public function i_can_owe_money_to_someone()
    {
        $me = factory(User::class)->create(['telegram_id' => 'han_solo', 'username' => 'hansolo']);
        $creditor = factory(User::class)->create(['telegram_id' => 'jabba_the_hutt', 'username' => 'jabbathehutt']);
        $group = factory(Group::class)->create(['telegram_id' => '789', 'type' => 'group', 'title' => 'Testing Group']);

        $creditor->addToGroup($group);

        $this->bot->setUser(['id' => 'han_solo', 'username' => 'hansolo'])
            ->receives('li dec 100 a @jabbathehutt', $this->getGroupPayload())
            ->assertReply(trans('debts.add.debt_me'));

        $this->assertDatabaseHas('debts', [
            'from_id' => $me->id,
            'to_id' => $creditor->id,
            'amount' => 100,
        ]);
    }

    /** @test */
    public function i_cannot_owe_money_to_a_no_registered_user()
    {
        $me = factory(User::class)->create(['telegram_id' => 'han_solo', 'username' => 'hansolo']);
        $group = factory(Group::class)->create(['telegram_id' => '789', 'type' => 'group', 'title' => 'Cantina']);

        $me->addToGroup($group);
        $this->bot->setUser(['id' => 'han_solo', 'username' => 'hansolo'])
            ->receives('li dec 100 a @jabbathehutt', $this->getGroupPayload())
            ->assertReply(trans('errors.user_not_found', ['username' => 'jabbathehutt']));
    }

    /** @test */
    public function someone_can_owe_me_money()
    {
        $me = factory(User::class)->create(['telegram_id' => 'jabba_the_hutt', 'username' => 'jabbathehutt']);
        $creditor = factory(User::class)->create(['telegram_id' => 'han_solo', 'username' => 'hansolo']);
        $group = factory(Group::class)->create(['telegram_id' => '789', 'type' => 'group', 'title' => 'Cantina']);

        $creditor->addToGroup($group);

        $this->bot->setUser(['id' => 'jabba_the_hutt', 'username' => 'jabbathehutt'])
            ->receives('@hansolo em deu 100', $this->getGroupPayload())
            ->assertReply(trans('debts.add.debt_others'));

        $this->assertDatabaseHas('debts', [
            'from_id' => $creditor->id,
            'to_id' => $me->id,
            'amount' => 100,
            'currency' => 'eur'
        ]);
    }

    /** @test */
    public function someone_cannot_owe_me_money_if_its_not_registered()
    {
        $creditor = factory(User::class)->create(['telegram_id' => 'jabba_the_hutt', 'username' => 'jabbathehutt']);
        $group = factory(Group::class)->create(['telegram_id' => '789', 'type' => 'group', 'title' => 'Cantina']);

        $creditor->addToGroup($group);
        $this->bot->setUser(['id' => 'jabba_the_hutt', 'username' => 'jabbathehutt'])
            ->receives('@hansolo em deu 100', $this->getGroupPayload())
            ->assertReply(trans('errors.user_not_found', ['username' => 'hansolo']));

    }
}
