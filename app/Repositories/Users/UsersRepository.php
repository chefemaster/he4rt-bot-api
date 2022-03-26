<?php


namespace App\Repositories\Users;


use App\Exceptions\DailyRewardException;
use App\Models\User\User;
use App\Transformers\ChecklistTransformer;
use Carbon\Carbon;

class UsersRepository
{
    /**
     * @var User
     */
    private $model;

    public function __construct()
    {
        $this->model = new User();
    }

    public function create(string $discordId)
    {
        $this->model->create([
            'discord_id' => $discordId
        ]);
        return $this;
    }

    public function findById(string $discordId, array $includes = [])
    {
        $user = $this->model
            ->where('discord_id', $discordId)
            ->first();


        if (in_array('checklist', $includes)) {
            $user->checklist = app(ChecklistTransformer::class)->handle($discordId);
        }

        return $user;
    }

    public function update(string $discordId, array $payload)
    {
        $this->model = $this->findById($discordId);
        $this->model->update($payload);

        return $this->model;
    }

    public function delete(string $discordId)
    {
        $this->findById($discordId)->delete();
        return true;
    }

    public function dailyPoints(string $discordId, bool $isDonator)
    {
        $this->model = $this->findById($discordId);

        if (!$this->validateReedemDailyPoints($this->model->daily)) {
            $time = Carbon::parse($this->model->daily)->timezone('America/Sao_Paulo')->toRfc3339String();
            throw new DailyRewardException($time);
        }
        $points = $this->generateDailyPoints($isDonator);
        $this->model = $this->model->dailyPoints(
            $points
        );
        return [
            'points' => $points,
            'date' => $this->model->daily
        ];
    }

    public function generateDailyPoints(bool $donator)
    {
        return $donator ? (rand(300, 1000) * 2) : rand(250, 500);
    }

    private function validateReedemDailyPoints($lastReedem): bool
    {
        if (!$lastReedem) {
            return true;
        }

        return Carbon::parse($lastReedem)->isPast();
    }


}
