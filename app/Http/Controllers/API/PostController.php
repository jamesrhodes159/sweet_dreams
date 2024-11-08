<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponser;
use App\Models\Conversation;
use App\Models\Notification;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Post;
use App\Models\PostLike;
use App\Models\PostComment;
use App\Models\PostSave;
use App\Models\PostReport;
use App\Models\PostHide;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Storage;
use Stichoza\GoogleTranslate\GoogleTranslate;
use App\Http\Controllers\API\CoreController;
use App\Models\PostMention;

class PostController extends Controller
{
    use ApiResponser;

    public function searchPost(Request $request)
    {
        $user = Auth::user();
        $controls=$request->all();
        $rules=array(  
            'title' => 'required'
        );
        $customMessages = [
            'required' => 'The :attribute field is required.',
            'unique' => 'The :attribute is Already Exists',
            'exists' => 'The :attribute is Not Exists',
        ];
        $validator=Validator::make($controls,$rules,$customMessages);
        if ($validator->fails()){
            return response()->json(['status'=>0,'message'=>$validator->errors()->all()[0]],400);
        }

        $posts = Post::whereNotIn('post_type', ['journal', 'Diario'])
            ->withCount('likes')
            ->withCount('comments')
            ->withCount('check_like')
            ->withCount('check_save')
            ->whereNotIn('id', function ($query) use ($user) {
                $query->select('post_id')
                    ->from(with(new PostHide)->getTable())
                    ->where('user_id', $user->id);
            })
            ->where(function ($query) use ($user) {
                $query->whereIn('post_type', ['public', 'Pública'])
                      ->orWhere(function ($query) use ($user) {
                          $query->whereIn('post_type', ['followers', 'Seguidoras'])
                                ->whereIn('user_id', function ($subQuery) use ($user) {
                                    $subQuery->select('follower_id')
                                             ->from('followers')
                                             ->where('user_id', $user->id);
                                });
                      })
                      ->orWhereIn('post_type', ['anonymous', 'Anónima']);
            })
            ->where('title','LIKE','%'.$request->title.'%')
            ->orderBy('id','DESC')
            ->get();

        if(count($posts)>0)
        {
            $data = PostResource::collection($posts);
            return $this->success("Post found successfully.",$data);
        }
        else
        {
            return $this->error('No data found.',400);
        }
    }

    public function searchFilterJournal(Request $request)
    {
        $user = Auth::user();
        $controls=$request->all();
        $rules=array(  
            'title' => 'nullable',
            'this_week' => 'nullable',
            'last_week' => 'nullable',
            'last_30_days' => 'nullable',
            'from_date' => 'nullable',
            'to_date' => 'nullable'
        );
        $customMessages = [
            'required' => 'The :attribute field is required.',
            'unique' => 'The :attribute is Already Exists',
            'exists' => 'The :attribute is Not Exists',
        ];
        $validator=Validator::make($controls,$rules,$customMessages);
        if ($validator->fails()){
            return response()->json(['status'=>0,'message'=>$validator->errors()->all()[0]],400);
        }


        $posts = Post::whereIn('post_type', ['journal', 'Diario'])
            ->withCount('likes')
            ->withCount('comments')
            ->withCount('check_like')
            ->withCount('check_save')
            ->whereNotIn('id', function ($query) use ($user) {
                $query->select('post_id')
                    ->from(with(new PostHide)->getTable())
                    ->where('user_id', $user->id);
            })
            ->where('title','LIKE','%'.$request->title.'%')
            ->orderBy('id','DESC');
            // ->get();

        $posts->when($request->has('title'), function ($query) use ($request)  {
            return $query->where('title','LIKE','%'.$request->title.'%');
        });

        // Apply the date filters
        $posts->where(function ($query) use ($request) {
            if ($request->has('this_week')) {
                $startOfWeek = Carbon::now()->startOfWeek();
                $endOfWeek = Carbon::now()->endOfWeek();
                $query->orWhereBetween('created_at', [$startOfWeek, $endOfWeek]);
            }

            if ($request->has('last_week')) {
                $startOfLastWeek = Carbon::now()->subWeek()->startOfWeek();
                $endOfLastWeek = Carbon::now()->subWeek()->endOfWeek();
                $query->orWhereBetween('created_at', [$startOfLastWeek, $endOfLastWeek]);
            }

            if ($request->has('last_30_days')) {
                $last30Days = Carbon::now()->subDays(30);
                $query->orWhere('created_at', '>=', $last30Days);
            }

            if ($request->has('from_date') && $request->has('to_date')) {
                $fromDate = Carbon::parse($request->input('from_date'))->startOfDay();
                $toDate = Carbon::parse($request->input('to_date'))->endOfDay();
                $query->orWhereBetween('created_at', [$fromDate, $toDate]);
            }
        });

        $posts = $posts->get();

        if(count($posts)>0)
        {
            $data = PostResource::collection($posts);
            return $this->success("Post found successfully.",$data);
        }
        else
        {
            return $this->error('No data found.',400);
        }
    }

    public function viewPosts(Request $request)
    {
        $user = Auth::user();
        // $posts = Post::where('post_type','!=','journal')
        //         ->withCount('likes')->withCount('comments')
        //         ->withCount('check_like')
        //         ->whereNotIn('id',function ($query) use ($user) {
        //             $query->select('post_id')
        //                 ->from(with(new PostHide)->getTable())
        //                 ->where('user_id', $user->id);
        //         })
        //     ->get();
        if($request->has('user_id'))
        {
            $allPosts = Post::whereNotIn('post_type', ['journal', 'Diario'])
            ->with('likes')
            ->withCount('likes')
            ->withCount('comments')
            ->withCount('check_like')
            ->withCount('check_save')
            // ->whereNotIn('id', function ($query) use ($user) {
            //     $query->select('post_id')
            //         ->from(with(new PostHide)->getTable());
            //         // ->where('user_id', $user->id);
            // })
            ->where(function ($query) use ($user) {
                $query->whereIn('post_type', ['public', 'Pública'])
                      ->orWhere(function ($query) use ($user) {
                          $query->whereIn('post_type', ['followers', 'Seguidoras'])
                                ->whereIn('user_id', function ($subQuery) use ($user) {
                                    $subQuery->select('follower_id')
                                             ->from('followers')
                                             ->where('user_id', $user->id);
                                });
                      })
                      ->orWhereIn('post_type', ['anonymous', 'Anónima']);
            })
            ->where('user_id',$request->user_id)
            ->orderBy('id','DESC')
            ->where('type','post')
            ->get();


        }
        else
        {
            $posts = Post::whereNotIn('post_type', ['journal', 'Diario'])
            ->with('likes')
            ->withCount('likes')
            ->withCount('comments')
            ->withCount('check_like')
            ->withCount('check_save')
            ->whereNotIn('id', function ($query) use ($user) {
                $query->select('post_id')
                    ->from(with(new PostHide)->getTable());
                    // ->where('user_id', $user->id);
            })
            ->where(function ($query) use ($user) {
                $query->whereIn('post_type', ['public', 'Pública'])
                      ->orWhere(function ($query) use ($user) {
                          $query->whereIn('post_type', ['followers', 'Seguidoras'])
                                // ->whereIn('user_id', function ($subQuery) use ($user) {
                                //     $subQuery->select('follower_id')
                                //              ->from('followers')
                                //              ->where('status','accepted')
                                //              ->where('user_id', $user->id);
                                // });
                                ->where(function ($subQuery) use ($user) {
                                    $subQuery->where('user_id', $user->id)
                                             ->orWhereIn('user_id', function ($innerQuery) use ($user) {
                                                 $innerQuery->select('follower_id')
                                                            ->from('followers')
                                                            ->where('status', 'accepted')
                                                            ->where('user_id', $user->id);
                                             });
                                });
                      })
                      ->orWhereIn('post_type', ['anonymous', 'Anónima']);
            })
        
            ->orderBy('id','DESC')
            ->where('type','post')
            ->get();


            // Fetch posts where the user is mentioned
            $mentionPostIds = PostMention::where('user_id', $user->id)
                                         ->pluck('post_id');

            $mentionedPosts = Post::whereIn('id', $mentionPostIds)
                                  ->with('likes')
                                  ->withCount('likes')
                                  ->withCount('comments')
                                  ->withCount('check_like')
                                  ->withCount('check_save')
                                  ->get();

            // Merge posts while avoiding duplicates
            $allPosts = $posts->merge($mentionedPosts)->unique('id')->sortByDesc('id')->values();
        }
        
        if(count($allPosts)>0)
        {
            if($request->has('user_id'))
            {
                foreach($allPosts as $post)
                {
                    $isHide = 0;
                    $check_hide = PostHide::where('post_id',$post->id)->where('user_id',$request->user_id)->first();
                    if($check_hide)
                    {
                        $isHide = 1;
                    }

                    $post['isHide'] = $isHide;
                }
            }
            $data = PostResource::collection($allPosts);
            // // return $this->success("Post found successfully.",$data);

            // $message = 'Post found successfully';
            
            // if($user->language == 'Spanish')
            // {
            //     $message = $this->changeLanguage($message);
            // }
            return $this->success('Post found successfully',$data);

        }
        else
        {
            // $message = 'No data found';
            // if($user->language == 'Spanish')
            // {
            //     $message = $this->changeLanguage($message);
            // }
            return $this->error('No data found',400);
        }
    }

    public function viewJournal()
    {
        $user = Auth::user();
        $posts = Post::where('post_type','journal')
                ->withCount('likes')->withCount('comments')
                ->withCount('check_like')
                ->whereNotIn('id',function ($query) use ($user) {
                    $query->select('post_id')
                        ->from(with(new PostHide)->getTable())
                        ->where('user_id', $user->id);
                })
                ->orderBy('id','DESC')
                ->where('type','post')
                ->get();
         if(count($posts)>0)
        {
            $data = PostResource::collection($posts);
            return $this->success("Post found successfully.",$data);
        }
        else
        {
            return $this->error('No data found.',400);
        }
    }

    public function viewDraft()
    {
        $user = Auth::user();
        $posts = Post::whereNotIn('post_type', ['journal', 'Diario'])
            ->withCount('likes')
            ->withCount('comments')
            ->withCount('check_like')
            ->withCount('check_save')
            // ->whereNotIn('id', function ($query) use ($user) {
            //     $query->select('post_id')
            //         ->from(with(new PostHide)->getTable());
            //         // ->where('user_id', $user->id);
            // })
            ->where(function ($query) use ($user) {
                $query->whereIn('post_type', ['public', 'Pública'])
                      ->orWhere(function ($query) use ($user) {
                          $query->whereIn('post_type', ['followers', 'Seguidoras'])
                                ->whereIn('user_id', function ($subQuery) use ($user) {
                                    $subQuery->select('follower_id')
                                             ->from('followers')
                                             ->where('user_id', $user->id);
                                });
                      })
                      ->orWhereIn('post_type', ['anonymous', 'Anónima']);
            })
            ->where('user_id',$user->id)
            ->orderBy('id','DESC')
            ->where('type','draft')
            ->get();

         if(count($posts)>0)
        {
            $data = PostResource::collection($posts);
            return $this->success("Drafts post found successfully.",$data);
        }
        else
        {
            return $this->error('No data found.',400);
        }
    }

    public function viewPostsDetail($id)
    {
        $user = Auth::user();
        $posts = Post::where('id',$id)->withCount('likes')->withCount('comments')
                ->withCount('check_like')->first();
        if($posts)
        {
            $data = new PostResource($posts);
            return $this->success("Post found successfully.",$data);
        }
        else
        {
            return $this->error('No data found.',400);
        }
    }

    public function createPost(Request $request)
    {
        $user = Auth::user();
        $controls=$request->all();
        $rules=array(
            'dream_type' =>  "required",  
            // 'title' => 'required',
            // "description" => "required",
            // "image" =>  "nullable",
            // "post_type" =>  "required",
            // "topic" => "nullable",
            // "feeling" => "nullable",
            // 'type' => 'required|in:draft,post',
            // 'mention' => 'nullable'
            'title' => 'required_if:type,post',
            'description' => 'required_if:type,post',
            'image' => 'nullable',
            'post_type' => 'required_if:type,post',
            'topic' => 'nullable',
            'feeling' => 'nullable',
            'type' => 'required|in:draft,post',
            'mention' => 'nullable',
        );
        $customMessages = [
            'required' => 'The :attribute field is required.',
            'unique' => 'The :attribute is Already Exists',
            'exists' => 'The :attribute is Not Exists',
        ];
        $validator=Validator::make($controls,$rules,$customMessages);
        if ($validator->fails()){
            return response()->json(['status'=>0,'message'=>$validator->errors()->all()[0]],400);
        }

        $data = $request->all();

        $data["user_id"] = $user->id;
        $data["topic"] = json_encode($request->topic);
        $data["feeling"] = json_encode($request->feeling);
        $data['type'] = $request->type;
        $post = Post::Create($data);

        if($request->hasFile('image')){
            $uploadFolder = 'post_image';

            $image = $request->image;
            $post->image = $image->store($uploadFolder, 'public');
        }

        $post->save();

        if($request->has('mention'))
        {
            foreach($request->mention as $mention)
            {
                $tag = new PostMention;
                $tag->post_id = $post->id;
                $tag->user_id = $mention;
                $tag->save();
            }
        }

        return $this->success('Post created successfully.',new PostResource($post));
    }

    public function editPost(Request $request)
    {
        $user = Auth::user();
        $controls=$request->all();
        $rules=array(
            'post_id' => 'required|exists:posts,id',
            'dream_type' =>  "required",  
            'title' => 'required_if:type,post',
            'description' => 'required_if:type,post',
            'image' => 'nullable',
            'post_type' => 'required_if:type,post',
            'topic' => 'nullable',
            'feeling' => 'nullable',
            'type' => 'required|in:draft,post',
            'mention' => 'nullable',
        );
        $customMessages = [
            'required' => 'The :attribute field is required.',
            'unique' => 'The :attribute is Already Exists',
            'exists' => 'The :attribute is Not Exists',
        ];
        $validator=Validator::make($controls,$rules,$customMessages);
        if ($validator->fails()){
            return response()->json(['status'=>0,'message'=>$validator->errors()->all()[0]],400);
        }

        $data = $request->all();
        $post = Post::find($request->post_id);

        if($request->hasFile('image')){
            $uploadFolder = 'post_image';

            $filePath = public_path('storage/' . $post->image);

            if($post->image != NULL){
                unlink($filePath);
            }
            
            $image = $request->image;
            $data["image"] = $image->store($uploadFolder, 'public');
        }

        $data["topic"] = json_encode($request->topic);
        $data["feeling"] = json_encode($request->feeling);
        $data['type'] = $request->type;
        $post->update($data);

        if($request->has('mention'))
        {
            PostMention::where('post_id',$request->post_id)->delete();
            foreach($request->mention as $mention)
            {
                $tag = new PostMention;
                $tag->post_id = $request->post_id;
                $tag->user_id = $mention;
                $tag->save();
            }
        }

        return $this->success('Post updated successfully.',new PostResource($post));
    }

    public function deletePost(Request $request)
    {
        $controls=$request->all();
        $rules=array(
            "post_id" => "required|exists:posts,id",       
        );
        $customMessages = [
            'required' => 'The :attribute  is required.',
            'exists' => 'The :attribute is Not Exists',
        ];
        $validator=Validator::make($controls,$rules,$customMessages);
         if ($validator->fails()) {
            return response()->json(['status'=>0,'message'=>$validator->errors()->all()[0]],400);
        }
        $post = Post::find($request->post_id);

        if ($post->image !== null) {
            Storage::disk('public')->delete($post->image);
        }

        // $post->unlinkFiles();
        $post->delete();
        if($post)
        {
            return $this->success('Post deleted successfully.');
        }
        else
        {
            return $this->error('Not deleted.', 400);
        }
    }

    public function likePost(Request $request)
    {
        $user = Auth::user();
        $controls=$request->all();
        $rules=array(
            "post_id"=>"required|exists:posts,id",
            "reaction" => "required"
        );
        $customMessages = [
        'required' => 'The :attribute field is required.',
        ];
        $validator=Validator::make($controls,$rules,$customMessages);
        if ($validator->fails()){
            return response()->json(['status'=>0,'message'=>$validator->errors()->all()[0]],400);
        }

        $post = Post::find($request->post_id);
        // $post = Post::where('id',$request->post_id)->withCount('likes')->withCount('comments')
        //         ->withCount('check_like')
        //         ->with('likes')
        //         ->withCount('likes')
        //         ->withCount('comments')
        //         ->withCount('check_like')
        //         ->withCount('check_save')
        //         ->first();
        
        $like_post = PostLike::where('post_id',$request->post_id)
                        ->where('user_id',$user->id)->where('reaction',$request->reaction)->first();
                        
        if($like_post){
            $unlike = PostLike::where('post_id',$request->post_id)
                        ->where('user_id',$user->id)->delete();
            if($unlike){
                $posts = Post::where('id',$request->post_id)
                        ->withCount('check_like')
                        ->with('likes')
                        ->withCount('likes')
                        ->withCount('comments')
                        ->withCount('check_like')
                        ->withCount('check_save')
                        ->first();
                return $this->success('Post Unliked.',new PostResource($posts));
            }

            
        }
        else{

            $like_post1 = PostLike::where('post_id',$request->post_id)
                        ->where('user_id',$user->id)->where('reaction','!=',$request->reaction)->first();
            if($like_post1)
            {
                $update = PostLike::where('post_id',$request->post_id)
                        ->where('user_id',$user->id)->update(["reaction" => $request->reaction]);
                if($update)
                {
                     $posts = Post::where('id',$request->post_id)
                        ->withCount('check_like')
                        ->with('likes')
                        ->withCount('likes')
                        ->withCount('comments')
                        ->withCount('check_like')
                        ->withCount('check_save')
                        ->first();
                    return $this->success('Post Liked successfully.',new PostResource($posts));
                }
            }
            
            else
            {
                $likes = new PostLike;
                $likes->post_id = $request->post_id;
                $likes->user_id = $user->id;
                $likes->reaction = $request->reaction;
                
                if($likes->save()){
                    
                    //Notification
                    $sender_id = $user->id;
                    $receiver_id = $post->user_id;
                    $type = "Post-Like";
                    $post_id = $request->post_id;
                    $message = $user->full_name." liked your post. ";
                   
                    if($sender_id != $receiver_id){
                        $notify = new CoreController;
                        $notify->add_notification($sender_id,$receiver_id,$type,$post_id,$message);
                        
                    }
                    
                    $posts = Post::where('id',$request->post_id)
                        ->withCount('check_like')
                        ->with('likes')
                        ->withCount('likes')
                        ->withCount('comments')
                        ->withCount('check_like')
                        ->withCount('check_save')
                        ->first();

                    return $this->success('Post Liked successfully.',new PostResource($posts));
                }
            
            
            }
            
        }
        
    }

    public function commentPost(Request $request)
    {
        $user = Auth::user();
        $controls=$request->all();
        $rules=array(
            "post_id"=>"required|exists:posts,id",
            "comment"=>"required"        );
        $customMessages = [
            'required' => 'The :attribute field is required.',
        ];
        $validator=Validator::make($controls,$rules,$customMessages);
        if ($validator->fails()){
            return response()->json(['status'=>0,'message'=>$validator->errors()->all()[0]],400);
        }
        
        $post = Post::find($request->post_id);
        
        $comments = new PostComment;
        $comments->post_id = $request->post_id;
        $comments->user_id = $user->id;
        $comments->comment = $request->comment;
        $comments->parent_id = 0;
        
       
        if($comments->save()){
            
            //Notification
            $sender_id = $user->id;
            $receiver_id = $post->user_id;
            $type = "Post-Comment";
            $post_id = $post->id;
            $message = $user->full_name." commented on your post. ";
            
            if($sender_id != $receiver_id){
                $notify = new CoreController;
                $notify->add_notification($sender_id,$receiver_id,$type,$post_id,$message);
            }

            $get_all_comments = PostComment::where('post_id',$request->post_id)
                        ->where('id',$comments->id)
                        ->with('user')
                        ->orderBy('id','DESC')
                        ->first();

            return $this->success('Post comment successfully.',$get_all_comments);
        }
        else{
            
            return $this->error('No post found.',400);
        }
    }

    public function commentDetail(Request $request)
    {
        $user = Auth::user();
        $controls=$request->all();
        $rules=array(
            "post_id"=>"required|exists:posts,id",
        );
        $customMessages = [
        'required' => 'The :attribute field is required.',
        ];
        $validator=Validator::make($controls,$rules,$customMessages);
        if ($validator->fails()){
            return response()->json(['status'=>0,'message'=>$validator->errors()->all()[0]],400);
        }    
        
        //$post = Post::where('id',$request->post_id)->with('comments','comments.user_profile:id,name,email,profile_image','comments.comment_like','comments.comment_reply','comments.check_like')->first();
        
        
        $post = PostComment::select(\DB::raw('id,user_id,post_id,comment,parent_id,created_at'))
                ->where('post_id',$request->post_id)
                ->where('parent_id',0)->where('like_id',0)
                ->with('user')
                ->orderBy('id','DESC')
                ->get();  ///->with('comment_reply')
        
        if(count($post)>0){

            return $this->success('Comment detail found successfully.',$post);
        }
        else{
            return $this->success('Not found.',$post);
        }
    }

    public function likeDetail(Request $request)
    {
        $user = Auth::user();
        $controls=$request->all();
        $rules=array(
            "post_id"=>"required|exists:posts,id",
        );
        $customMessages = [
        'required' => 'The :attribute field is required.',
        ];
        $validator=Validator::make($controls,$rules,$customMessages);
        if ($validator->fails()){
            return response()->json(['status'=>0,'message'=>$validator->errors()->all()[0]],400);
        }    
        
        //$post = Post::where('id',$request->post_id)->with('comments','comments.user_profile:id,name,email,profile_image','comments.comment_like','comments.comment_reply','comments.check_like')->first();
        
        
        $post = PostLike::where('post_id',$request->post_id)
                ->with('user')
                ->orderBy('id','DESC')
                ->get();  ///->with('comment_reply')
        
        if(count($post)>0){

            return $this->success('Comment detail found successfully.',$post);
        }
        else{
            return $this->error('Not found.',400);
        }
    }


    public function reportPost(Request $request)
    {
        $user = Auth::user();
        $controls=$request->all();
        $rules=array(
            "post_id" => "required|exists:posts,id",  
            "reason" => "required"     
        );
        $customMessages = [
            'required' => 'The :attribute  is required.',
            'exists' => 'The :attribute is Not Exists',
        ];
        $validator=Validator::make($controls,$rules,$customMessages);
         if ($validator->fails()) {
            return response()->json(['status'=>0,'message'=>$validator->errors()->all()[0]],400);
        }

        $post = Post::where('id',$request->post_id)->first();

        $check = PostReport::where('post_id',$request->post_id)->where('user_id',$user->id)->first();
        if($check)
        {
            return $this->error("Already reported.",400);
        }
        else
        {
            $report = new PostReport;
            $report->post_id = $request->post_id;
            $report->user_id = $user->id;
            $report->reason = $request->reason;
            $report->save();

            //Notification
                $sender_id = $user->id;
                $receiver_id = $post->user_id;
                $type = "Report-Post";
                $post_id = $request->post_id;
                $message = $user->full_name." reported your post. ";
               
                if($sender_id != $receiver_id){
                    $notify = new CoreController;
                    $notify->add_notification($sender_id,$receiver_id,$type,$post_id,$message);
                    
                }

            return $this->success('Post reported successfully.');
        }
    }


    public function savePost(Request $request)
    {
        $user = Auth::user();
        $controls=$request->all();
        $rules=array(
            "post_id" => "required|exists:posts,id",       
        );
        $customMessages = [
            'required' => 'The :attribute  is required.',
            'exists' => 'The :attribute is Not Exists',
        ];
        $validator=Validator::make($controls,$rules,$customMessages);
         if ($validator->fails()) {
            return response()->json(['status'=>0,'message'=>$validator->errors()->all()[0]],400);
        }

        $post = Post::where('id',$request->post_id)->first();

        $check = PostSave::where('post_id',$request->post_id)->where('user_id',$user->id)->first();
        if($check)
        {
            // return $this->error("Already saved.",400);
            $unsave = PostSave::where('post_id',$request->post_id)
                        ->where('user_id',$user->id)->delete();
            if($unsave){
                return $this->success('Post Unsaved.');
            }
        }
        else
        {
            $report = new PostSave;
            $report->post_id = $request->post_id;
            $report->user_id = $user->id;
            $report->save();

            // //Notification
            //     $sender_id = $user->id;
            //     $receiver_id = $post->user_id;
            //     $type = "Save-Post";
            //     $post_id = $request->post_id;
            //     $message = $user->first_name." ".$user->last_name." reported your post. ";
               
            //     if($sender_id != $receiver_id){
            //         $notify = new CoreController;
            //         $notify->add_notification($sender_id,$receiver_id,$type,$post_id,$message);
                    
            //     }

            return $this->success('Post saved successfully.');
        }
    }

    public function hidePost(Request $request)
    {
        $user = Auth::user();
        $controls=$request->all();
        $rules=array(
            "post_id" => "required|exists:posts,id",       
        );
        $customMessages = [
            'required' => 'The :attribute  is required.',
            'exists' => 'The :attribute is Not Exists',
        ];
        $validator=Validator::make($controls,$rules,$customMessages);
         if ($validator->fails()) {
            return response()->json(['status'=>0,'message'=>$validator->errors()->all()[0]],400);
        }

        $post = Post::where('id',$request->post_id)->first();

        $check = PostHide::where('post_id',$request->post_id)->where('user_id',$user->id)->first();
        if($check)
        {
            $unlike = PostHide::where('post_id',$request->post_id)
                        ->where('user_id',$user->id)->delete();
            return $this->success("Unhide post successfully.");
        }
        else
        {
            $report = new PostHide;
            $report->post_id = $request->post_id;
            $report->user_id = $user->id;
            $report->save();

            //Notification
                // $sender_id = $user->id;
                // $receiver_id = $post->user_id;
                // $type = "Hide-Post";
                // $post_id = $post->group_id;
                // $message = $user->first_name." ".$user->last_name." reported your post. ";
               
                // if($sender_id != $receiver_id){
                //     $notify = new CoreController;
                //     $notify->add_notification($sender_id,$receiver_id,$type,$post_id,$message);
                    
                // }

            return $this->success('Post hide successfully.');
        }
    }

    public function viewSavePost()
    {
        $user = Auth::user();

        // $save = PostSave::with('post')->where('user_id',$user->id)->orderBy('id','DESC')->get();
        $save = PostSave::with(['post' => function($query) {
                $query->withCount(['likes', 'comments', 'check_like', 'check_save']);
            }])
            ->where('user_id', $user->id)
            ->orderBy('id', 'DESC')
            ->get();


        if(count($save)>0)
        {
            $data = PostResource::collection($save->pluck('post'));
            return $this->success("Save post found successfully.",$data);
        }
        else
        {
            return $this->error('No data found.',400);
        }
    }

   
}