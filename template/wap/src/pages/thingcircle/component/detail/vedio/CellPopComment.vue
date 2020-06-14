<template>
  <van-popup v-model="isShow" position="bottom" :overlay="true">
    <div class="header-com">
      <h3>全部评论</h3>
      <van-icon name="close" size="16px" @click="isShow = false" />
    </div>
    <div class="com-body">
      <CellComment :tid="tid" @click-answer="onAnswer" ref="commmet">
        <div slot="bottomFixed" class="fixed-input">
          <div class="msg-input">
            <van-field type="text" :placeholder="placeholder" maxlength="200" v-model="message" />
            <div class="btn-s" @click="addComment()">发送</div>
          </div>
        </div>
      </CellComment>
    </div>
  </van-popup>
</template>

<script>
import CellComment from "../CellComment";
import {
  ADD_THINGCIRCLECOMMENT,
  REPLY_THINGCIRCLECOMMENT
} from "@/api/thingcircle";
export default {
  data() {
    return {
      isShow: false,
      message: "",
      placeholder: "说点什么",
      editorial: {}
    };
  },
  props: {
    tid: [String, Number],
    topic_id: [String, Number]
  },
  mounted() {},
  methods: {
    addComment() {
      const $this = this;
      let params = {
        thing_id: $this.tid,
        topic_id: $this.topic_id ? $this.topic_id : "",
        content: $this.message
      };

      let reply_params = {
        thing_id: $this.tid,
        topic_id: $this.topic_id ? $this.topic_id : "",
        content: $this.message,
        to_uid: $this.editorial.to_uid,
        comment_pid: $this.editorial.comment_pid
      };

      if (!$this.message.replace(/\s*/g, "")) {
        $this.$Toast("没有什么想说的吗");
        return false;
      }

      if (
        $this.editorial.hash == "#childComment" ||
        $this.editorial.hash == "#parentComment"
      ) {
        REPLY_THINGCIRCLECOMMENT(reply_params).then(({ data }) => {
          $this.$Toast("发表成功");
          $this.message = "";
          $this.editorial.hash = "";
          $this.placeholder = "说点什么";
          $this.nextLoad();
        });
      } else {
        ADD_THINGCIRCLECOMMENT(params).then(({ data }) => {
          $this.$Toast("发表成功");
          $this.message = "";
          $this.placeholder = "说点什么";
          $this.nextLoad();
        });
      }
    },
    onAnswer(params) {
      const $this = this;
      $this.editorial.to_uid = params.to_uid;
      $this.editorial.comment_pid = params.comment_pid;
      $this.editorial.people = params.people;
      $this.editorial.hash = params.hash;

      $this.placeholder = $this.editorial.hash
        ? "回复" + $this.editorial.people + ":"
        : "说点什么";
    },
    nextLoad(){
      this.$refs.commmet.loadList("init");
    }
  },
  components: {
    CellComment
  }
};
</script>

<style scoped>
.popup-com-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.4);
  z-index: 2100;
}
.popup-com-bottom {
  width: 100%;
  top: auto;
  bottom: 0;
  right: auto;
  left: 50%;
  -webkit-transform: translate3d(-50%, 0, 0);
  transform: translate3d(-50%, 0, 0);
  position: fixed;
  max-height: 100%;
  overflow-y: auto;
  background-color: #fff;
  -webkit-transition: 0.3s ease-out;
  transition: 0.1s ease-out;
  z-index: 2101;
  border-top-left-radius: 10px;
  border-top-right-radius: 10px;
}
.header-com {
  width: 100%;
  height: 40px;
  text-align: center;
  color: #333;
  line-height: 40px;
  position: relative;
  background-color: #fff;
}
.header-com h3 {
  font-weight: normal;
  font-size: 15px;
}
.header-com >>> .van-icon {
  position: absolute;
  top: 10px;
  right: 10px;
}
.header-com::after {
  content: "";
  position: absolute;
  pointer-events: none;
  -webkit-box-sizing: border-box;
  box-sizing: border-box;
  bottom: 0;
  left: 0;
  width: 100%;
  transform: scaleY(0.5);
  height: 1px;
  background-color: #ebedf0;
}
.com-body {
  position: relative;
  height: 420px;
}
.com-body >>> .section {
  height: 420px;
  position: relative;
}
.com-body >>> .section .list {
  position: relative;
  height: calc(100% - 46px);
  overflow-y: scroll;
}
.msg-input {
  display: flex;
  width: 100%;
  align-items: center;
}
.msg-input >>> .van-field__control { 
  border: 1px solid #dadada;
  border-radius: 20px;
}
.btn-s {
  width: 40px;
  height: 100%;
  color: #1989fa;
}
.msg-input >>> .van-field__control {
  padding-left: 10px;
}
.fixed-input {
  width: 100%;
  height: 46px;
  background-color: #fff;
  z-index: 1;
}
.fixed-input::before {
  content: "";
  position: absolute;
  pointer-events: none;
  -webkit-box-sizing: border-box;
  box-sizing: border-box;
  top: -1px;
  left: 0;
  width: 100%;
  transform: scaleY(0.5);
  height: 1px;
  background-color: #ebedf0;
}
</style>