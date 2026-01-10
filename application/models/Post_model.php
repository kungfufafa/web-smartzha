<?php

/*   ________________________________________
    |                 GarudaCBT              |
    |    https://github.com/garudacbt/cbt    |
    |________________________________________|
*/
class Post_model extends CI_Model
{
    public function getPostUser($id_user)
    {
        $this->db->select("a.*, b.nama_guru, b.foto, (SELECT COUNT(post_comments.id_comment) FROM post_comments WHERE a.id_post = post_comments.id_post) AS jml");
        $this->db->from("post a");
        $this->db->join("master_guru b", "a.dari=b.id_guru", "left");
        if ($id_user != 0) {
            $this->db->where("a.dari", $id_user);
        }
        $this->db->order_by("a.updated", "desc");
        $posts = $this->db->get()->result();
        return $posts;
    }

    public function getPostForUser($kepada, $kelas = null)
    {
        $this->db->select("a.*, b.nama_guru, b.foto, (SELECT COUNT(post_comments.id_comment) FROM post_comments WHERE a.id_post = post_comments.id_post) AS jml");
        $this->db->from("post a");
        $this->db->join("master_guru b", "a.dari=b.id_guru", "left");
        if ($kepada != null) {
            $this->db->where("(a.kepada LIKE " . $kepada . ") OR (a.kepada LIKE " . $kelas . ")");
        }
        $this->db->order_by("a.updated", "desc");
        $posts = $this->db->get()->result();
        return $posts;
    }

    public function getIdComments($id_post)
    {
        $this->db->select("id_comment");
        $this->db->where("id_post", $id_post);
        $ids = $this->db->get("post_comments")->result();
        return $ids;
    }

    public function getIdReplies($id_comment)
    {
        $this->db->select("id_reply");
        if ( ! safe_where_in($this->db, "id_comment", $id_comment)) {
            return [];
        }
        $ids = $this->db->get("post_reply")->result();
        return $ids;
    }
}
