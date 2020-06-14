<template>
  <div class="section">
    <List
      class="list"
      v-model="loading"
      :finished="finished"
      :error.sync="error"
      :is-empty="isListEmpty"
      :empty="{message: '暂无评论', showFoot: false}"
      @load="loadList"
    >
      <div class="comment" v-for="(items,index) in list" :key="index">
        <!--commentInfo-->
        <div class="comment-info">
          <div class="user">
            <div class="avatar avatar-M type-outline">
              <div class="avatar-img">
                <img
                  :src="items.user_headimg ? items.user_headimg : $ERRORPIC.noAvatar"
                  :onerror="$ERRORPIC.noAvatar"
                />
              </div>
            </div>
            <div class="user-info">
              <h4 class="user-nickname">
                <label>{{items.thing_user_name ? items.thing_user_name : '匿名'}}</label>
                <van-tag type="danger" v-if="items.is_author">作者</van-tag>
              </h4>
              <span class="publish-time">{{items.create_time | getTimer}}</span>
            </div>
          </div>

          <div class="comment-stats" @click="onFabulous(items)">
            <span class="likes">
              <van-icon
                :name="items.is_like ? 'like' : 'like-o'"
                :class="items.is_like ? color.yes : color.no"
                size="14px"
              />
              <label>{{items.like_count | praise}}</label>
            </span>
          </div>
        </div>
        <!--commentContentText-->
        <p class="content-text" @click="onReflex(items,'#parentComment',items.id)">{{items.content}}</p>
        <!--commmetReplies-->
        <div class="replies" v-if="items.reply_list.total_count > 0">
          <div class="reply" v-for="(child,cindex) in items.reply_list.data" :key="cindex">
            <div class="replier">
              <div class="cube-image">
                <img :src="child.user_headimg" :onerror="$ERRORPIC.noAvatar" />
              </div>
              <div class="rep-name">
                <label>{{child.thing_user_name ? child.thing_user_name : "匿名"}}</label>
                <van-tag type="danger" v-if="child.is_author">作者</van-tag>
              </div>
            </div>
            <div class="rep-content" @click="onReflex(child,'#childComment',items.id)">
              <span>回复</span>
              <span class="to">{{child.to_thing_user_name ? child.to_thing_user_name : "匿名"}}</span>
              ：{{child.content}}
            </div>
            <div class="rep-stats" @click="onFabulous(child,'child')">
              <van-icon
                :name="child.is_like ? 'like' : 'like-o'"
                :class="child.is_like ? color.yes : color.no"
                size="14px"
              />
              <span class="num">{{child.comment_likes | praise}}</span>
            </div>
          </div>
          <p class="sub-total" v-if="items.reply_list.total_count > 1">
            <span
              @click="onMoreReply(items.thing_id,items.id,index)"
              v-if="is_more[index]"
              class="text"
            >展开更多回复</span>
          </p>
        </div>
      </div>
    </List>
    <Popup :isShow="show">
      <div slot="content" class="pupop-wrap">
        <div class="cell">
          <div @click="onAnswer">回复</div>
          <div v-if="uid == from_uid" @click="onDelete">删除</div>
          <div v-else @click="onReport">举报</div>
        </div>
        <div class="cancle" @click="show = false">取消</div>
      </div>
    </Popup>
    <slot name="bottomFixed"></slot>
  </div>
</template>

<script>
import { Loading } from "vant";
import { list } from "@/mixins";
import { setSession } from "@/utils/storage";
import Popup from "./Popup";
import {
  GET_THINGCIRCLEREPLY,
  GET_COMMENTLIST,
  GET_THINGCIRCLELIKESCOMMENT,
  DEL_THINGCIRCLECOMMENT
} from "@/api/thingcircle";
export default {
  data() {
    return {
      color: {
        yes: "cr-ff454e",
        no: "cr-999"
      },
      params: {
        page_index: 1,
        page_size: 10,
        thing_id: ""
      },
      show: false,
      ans: {},

      is_more: [],

      from_uid: null,
      is_self: null,
      is_author: null,

      flag_like: true
    };
  },
  props: {
    user_id: [String, Number],
    tid: [String, Number]
  },
  mixins: [list],
  computed: {
    thing_id() {
      return this.$route.params.thingid;
    },
    uid() {
      let uid = null;
      if (this.$store.state.member.info) {
        uid = this.$store.state.member.info.uid;
      }
      return uid;
    }
  },
  watch: {
    tid(val) {
      return this.loadList("init");
    }
  },
  mounted() {
    this.loadList();
  },
  methods: {
    loadList(init) {
      const $this = this;
      if (init && init === "init") {
        $this.initList();
      }
      $this.is_more = [];
      $this.params.thing_id = $this.tid ? $this.tid : $this.thing_id;
      GET_COMMENTLIST($this.params).then(({ data }) => {
        let list = data.data;
        data.data.forEach(e => {
          if (e.reply_list.total_count > 1) {
            $this.is_more.push(1);
          } else {
            $this.is_more.push(0);
          }
        });
        $this.pushToList(list, data.page_count, init);
      });
    },
    //点赞
    onFabulous(data, hash) {
      const $this = this;
      let params = {
        comment_id: data.id
      };
      if ($this.flag_like == false) {
        return false;
      }
      $this.flag_like = false;
      GET_THINGCIRCLELIKESCOMMENT(params).then(res => {
        if (res.code == 1) {
          if (res.message == "取消点赞成功") {
            data.is_like = 0;
          } else {
            data.is_like = 1;
          }
          data.comment_likes = res.count;
          data.like_count = res.count;
        }
        $this.flag_like = true;
      });
    },
    //显隐弹出框
    onReflex(data, hash, id) {
      this.show = true;
      this.from_uid = data.from_uid;
      this.ans = {
        data: data,
        hash: hash,
        id: id
      };
    },
    //回复
    onAnswer() {
      this.show = false;
      let reply_params = {
        to_uid: this.ans.data.from_uid,
        comment_pid: this.ans.id,
        people: this.ans.data.thing_user_name
          ? this.ans.data.thing_user_name
          : "匿名",
        hash: this.ans.hash
      };
      this.$emit("click-answer", reply_params);
    },
    //展开更多回复
    onMoreReply(thing_id, id, index) {
      const $this = this;
      let params = {
        thing_id: thing_id,
        comment_pid: id
      };
      GET_THINGCIRCLEREPLY(params).then(({ data }) => {
        $this.list[index].reply_list.data = data.data;
        $this.is_more[index] = 0;
      });
    },
    //删除
    onDelete() {
      const $this = this;
      $this.isLoign();
      let params = {
        comment_id: this.ans.data.id
      };
      DEL_THINGCIRCLECOMMENT(params).then(res => {
        if (res.code == 1) {
          $this.$Toast("删除成功!");
          $this.show = false;
          $this.loadList("init");
        }
      });
    },
    //举报
    onReport() {
      this.isLoign();
      this.$router.push({
        name: "thingcircle-report",
        params: {
          commentid: this.ans.data.id
        }
      });
    },
    isLoign() {
      const $this = this;
      if (!$this.$store.getters.token) {
        setSession("toPath", $this.$router.currentRoute.fullPath);
        $this.$router.replace({ name: "login" });
      } else {
        return false;
      }
    }
  },
  components: {
    Popup,
    [Loading.name]: Loading
  }
};
</script>

<style scoped>
.list >>> .empty .img {
  display: none;
}
.section {
  position: relative;
}
.comment {
  position: relative;
  padding: 15px 15px 15px 0;
  margin-left: 15px;
}
.comment .comment-info {
  position: relative;
  zoom: 1;
  overflow: hidden;
}
.comment .comment-info:after,
.comment .comment-info:before {
  content: "";
  display: table;
}
/******user***** */
.user {
  float: left;
  zoom: 1;
}
.use:after,
.user:before {
  content: "";
  display: table;
}
.user .avatar {
  float: left;
}
.avatar {
  position: relative;
  display: block;
}
.avatar.avatar-M {
  width: 42px;
  height: 42px;
}
.avatar.type-outline {
  position: relative;
  border: none;
}
.avatar.type-outline:before {
  content: "";
  position: absolute;
  width: 200%;
  height: 200%;
  top: 0;
  left: 0;
  transform-origin: 0 0;
  transform: scale(0.5);
  box-sizing: border-box;
  pointer-events: none;
  border: 1px solid #e6e6e6;
  border-radius: 50%;
}
.avatar.type-outline {
  position: relative;
}
.avatar .avatar-img {
  display: block;
  width: 100%;
  height: 100%;
  font-size: 0;
  border-radius: 50%;
  overflow: hidden;
  z-index: 10;
}
.avatar .avatar-img img {
  display: block;
  width: 100%;
  height: auto;
  opacity: 1;
  transition: opacity 0.2s ease-in;
}
.user .user-info {
  float: left;
  margin-left: 10px;
  margin-top: 4px;
}
.user .user-nickname {
  margin: 0;
  font-size: 15px;
  font-weight: 500;
  line-height: 17px;
  color: #333;
  overflow: hidden;
  max-width: 180px;
  display: flex;
  align-items: center;
}
.user .user-nickname label {
  text-overflow: ellipsis;
  overflow: hidden;
  max-width: 120px;
  white-space: nowrap;
  display: inline-block;
  margin-right: 4px;
  color: #0642a4;
}
.user .publish-time {
  display: inline-block;
  margin-top: 5px;
  font-size: 10px;
  line-height: 12px;
  color: #999;
}
/************ */
.comment-stats {
  float: right;
  font-size: 12px;
  line-height: 24px;
  text-align: center;
}
.comment-stats span {
  cursor: pointer;
}
.comment-stats .likes >>> .van-icon {
  display: block;
  margin: 0 2px;
  text-align: right;
}
.comment-stats .likes label {
  font-size: 12px;
}
.content-text {
  position: relative;
  margin: 10px 0 0 52px;
  font-size: 15px;
  line-height: 22px;
  word-break: break-all;
}
/***replies***/
.replies {
  zoom: 1;
  position: relative;
  padding: 10px;
  margin: 10px 0 0 42px;
  background-color: #f5f5f5;
  border-radius: 8px;
  font-size: 13px;
  line-height: 20px;
  overflow: hidden;
}
.replies:after,
.replies:before {
  content: "";
  display: table;
}
.replies .reply:first-child {
  margin-top: 0px;
}
.replies .reply {
  margin-top: 10px;
}
.reply {
  padding: 0;
  position: relative;
}
.replier {
  color: #333;
  font-weight: 500;
  display: flex;
  overflow: hidden;
  align-items: center;
}
.replier .cube-image {
  width: 26px;
  height: 26px;
  margin-right: 4px;
}
.cube-image img {
  display: block;
  width: 100%;
  height: auto;
  border-radius: 50%;
}
.rep-name {
  flex: 1;
  display: flex;
  align-items: baseline;
}
.rep-name label {
  font-size: 14px;
  color: #0642a4;
  max-width: 100px;
  overflow: hidden;
  display: inline-block;
  white-space: nowrap;
  text-overflow: ellipsis;
  margin-right: 4px;
}
.rep-content {
  font-size: 14px;
  margin-left: 30px;
  line-height: 20px;
  margin-top: 6px;
  margin-right: 16px;
  word-break: break-all;
}
.rep-content .to {
  color: #0642a4;
}
.rep-stats {
  position: absolute;
  top: 0;
  right: 0;
  text-align: center;
  font-size: 12px;
}
.rep-stats >>> .van-icon {
  display: block;
  text-align: right;
}
.rep-stats .num {
  display: block;
}
.sub-total {
  margin: 10px 0 0;
  margin-left: 30px;
}
.sub-total span.text {
  color: #5b92e1;
  font-size: 14px;
  display: block;
}
/*.pupop-wrap >>> .van-popup--bottom {
  width: 90%;
  border-radius: 10px;
  bottom: 10px;
}*/
.pupop-wrap .cell {
  background-color: #fff;
}
.pupop-wrap .cell div,
.cancle {
  color: #323233;
  font-size: 14px;
  line-height: 24px;
  background-color: #fff;
  padding: 10px 15px;
  text-align: center;
  color: #5b92e1;
  position: relative;
}
.pupop-wrap .cell div::before,
.cancle::before {
  content: "";
  position: absolute;
  pointer-events: none;
  -webkit-box-sizing: border-box;
  box-sizing: border-box;
  bottom: 0;
  left: 0;
  width: 100%;
  -webkit-transform: scaleY(0.5);
  transform: scaleY(0.5);
  height: 1px;
  background-color: #ebedf0;
}

/*******/
.cr-ff454e {
  color: #ff454e;
}
.cr-999 {
  color: #999;
}
</style>