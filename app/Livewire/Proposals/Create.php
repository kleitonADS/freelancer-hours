<?php

namespace App\Livewire\Proposals;
use App\Models\Project;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class Create extends Component


{

    public Project $project;
    public bool $modal = false;

    #[Rule(['required', 'email'])]
    public string $email = '';

    #[Rule(['required', 'numeric', 'gt:0'])]
    public int $hours = 0;

    public bool $agree = false;

    public function save(){



        $this->validate();

        if(!$this->agree){
            $this->addError('agree', 'Você precisa concordar com os termos de uso');
            return;
        }

        $proposal = $this->project->proposals()

        ->updateOrCreate(

           [ 'email' => $this->email ],
           [ 'hours' => $this->hours ],
        );

        $this->arrangePosition( $proposal);


        $this->dispatch('proposal::created');
        $this->modal = false;

    }

    public function arrangePosition(Proposal $proposal){

        $query = DB::select("

                select *, row_number() over ( order by hours asc ) as newPosition
                from proposals
                where project_id = :project

        ", ['project' => $proposal->project_id]);

        $position = collect($query)->where('id', '=', $proposal->id)->first();
        $otherProposal = collect($query)->where('position', '=', $position->newPosition)->first();


        if($otherProposal) {
            $proposal->update(['position_status' => 'up']);
            Proposal::query()->where('id', '=', $otherProposal->id)->update(['position_status', 'down']);
        }

    }


    public function render()
    {
        return view('livewire.proposals.create');
    }
}
