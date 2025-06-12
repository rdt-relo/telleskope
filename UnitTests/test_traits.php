<?php
exit();
// In order to use this file first create tables topic_likes and topic_comments;
require_once __DIR__ .'/../affinity/head.php';
$postid = 416; // To test, set postid to a valid value

//Comment::DeleteComment_2(1,3);
//exit();
echo "<h5>Likes</h5>";
// Get the total post likes
echo Post::GetLikeTotals($postid)."<br>";
// Get my like status
echo Post::GetUserLikeStatus($postid)."<br>";
// Like or unlike the post
Post::LikeUnlike($postid);
// Get my like status
echo Post::GetUserLikeStatus($postid)."<br>";
// Get the total post likes
echo Post::GetLikeTotals($postid)."<br>";

echo "<h5>Comments</h5>";
$pc = Post::CreateComment_2($postid,'Post Comment'.hrtime(true),'');
$pc_a = Comment::CreateComment_2($pc,'Subcomment '.hrtime(true),'');
$pc_b = Comment::CreateComment_2($pc,'Subcomment '.hrtime(true),'');
$pc_bb = Comment::CreateComment_2($pc_b,'Subcomment x2 '.hrtime(true),'');
$pc_bbb = Comment::CreateComment_2($pc_bb,'Subcomment x3 '.hrtime(true),'');
$pc_bbc = Comment::CreateComment_2($pc_bb,'Subcomment x3 '.hrtime(true),'');
$pc_bbbd = Comment::CreateComment_2($pc_bbb,'Subcomment x4 '.hrtime(true),'');
$pc_c = Comment::CreateComment_2($pc,'Subcomment '.hrtime(true),'');

Comment::LikeUnlike($pc_b);
Comment::LikeUnlike($pc_bb);
Comment::LikeUnlike($pc_bbb);
Comment::LikeUnlike($pc_bbbd);
//
// To update Post comment
Post::UpdateComment_2($postid,$pc, 'Post Comment Update '.hrtime(true));

// To update subcomment
Comment::UpdateComment_2($pc_b,$pc_bb, 'Subcomment Update '.hrtime(true));

// To like/unlike comment
echo Comment::LikeUnlike($pc_a);

// To delete nested subcomment
Comment::DeleteComment_2($pc_bb,$pc_bbb);

echo "Total comments for post = ".Post::GetCommentTotals_2($postid)."<br>";

// An example of how to get comments for a post.
$comments = Post::GetComments_2($postid);
foreach ($comments as $comment) {
    echo "<hr>";
    echo "<pre>" . json_encode($comment, JSON_PRETTY_PRINT) . "</pre>";

    // If the subcomment_count > 0 then there are subcomments that you can get as well.
    if ($comment['subcomment_count']) {
        $sub_comments = Comment::GetComments_2($comment['commentid']);
        foreach ($sub_comments as $sub_comment) {
            echo "<span style='color:blue'><pre>" . json_encode($sub_comment, JSON_PRETTY_PRINT) . "</pre></span>";
        }
    }
}
//
//echo Comment::DeleteComment_2(3,4);