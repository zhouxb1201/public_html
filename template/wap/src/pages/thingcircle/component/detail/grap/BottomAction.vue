<template>
  <div>
    <div class="wrap">
      <div class="bom-fixed">
        <div class="prt">
          <div class="text-input" @click="onPopMessage">
            <van-icon name="edit" size="14px" />
            <span>说点什么...</span>
          </div>
          <div class="icon-w">
            <van-icon
              :name="editorial.is_like ? 'like' : 'like-o'"
              :class="editorial.is_like ? color.yes : color.no"
              size="20px"
              @click="onFabulous()"
            />
            <span>{{editorial.likes | praise}}</span>
          </div>
          <div class="icon-w">
            <van-icon
              :name="editorial.is_collect ? 'star' : 'star-o'"
              :class="editorial.is_collect ? color.yes : color.no"
              size="20px"
              @click="onCollection()"
            />
            <span>{{editorial.collects | praise}}</span>
          </div>
          <div class="icon-w" @click="onChat">
            <van-icon name="chat-o" :class="color.no" size="20px" />
            <span></span>
          </div>
        </div>
      </div>
    </div>
    <van-popup v-model="is_show" position="bottom" :overlay="true">
      <div class="msg-input">
        <van-field
          type="text"
          :placeholder="placeholder"
          maxlength="200"
          v-model="message"
          @focus="onAnswer"
          ref="inputMessage"
        />
        <div class="btn-s" @click="addComment">发送</div>
      </div>
    </van-popup>
  </div>
</template>

<script>
import {
  ADD_THINGCIRCLECOMMENT,
  GET_THINGCIRCLELIKES,
  GET_THINGCIRCLECOLLECTION,
  REPLY_THINGCIRCLECOMMENT
} from "@/api/thingcircle";
export default {
  data() {
    return {
      message: "",
      items: [],
      color: {
        yes: "cr-ff454e",
        no: "cr-999"
      },
      placeholder: "说点什么...",
      flag: true,
      is_show: false
    };
  },
  props: {
    editorial: [Object]
  },
  watch: {
    is_show(value) {
      if (value == false) {
        const $this = this;
        $this.placeholder = "说点什么...";
        $this.message = "";
        if ($this.editorial.to_uid) {
          delete $this.editorial.to_uid;
        }
        if ($this.editorial.comment_pid) {
          delete $this.editorial.comment_pid;
        }
        if ($this.editorial.people) {
          delete $this.editorial.people;
        }
        if ($this.editorial.hash) {
          delete $this.editorial.hash;
        }
      }
    }
  },
  methods: {
    addComment() {
      const $this = this;

      let params = {
        thing_id: $this.editorial.thing_id,
        topic_id: $this.editorial.topic_id ? $this.editorial.topic_id : "",
        content: $this.message
      };

      let reply_params = {
        thing_id: $this.editorial.thing_id,
        topic_id: $this.editorial.topic_id ? $this.editorial.topic_id : "",
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
        if ($this.flag == false) {
          return false;
        }
        $this.flag = false;
        REPLY_THINGCIRCLECOMMENT(reply_params).then(({ data }) => {
          $this.$Toast("发布成功");
          $this.message = "";
          $this.is_show = false;
          $this.editorial.hash = "";
          $this.placeholder = "说点什么...";
          $this.$emit("clickActive");
          $this.flag = true;
        });
      } else {
        if ($this.flag == false) {
          return false;
        }
        $this.flag = false;
        ADD_THINGCIRCLECOMMENT(params).then(({ data }) => {
          $this.$Toast("发布成功");
          $this.message = "";
          $this.is_show = false;
          $this.placeholder = "说点什么...";
          $this.$emit("clickActive");
          $this.flag = true;
        });
      }
    },
    onFabulous() {
      const $this = this;
      let params = {
        thing_id: $this.editorial.thing_id
      };
      GET_THINGCIRCLELIKES(params).then(res => {
        if (res.code == 1) {
          if (res.message == "取消点赞成功") {
            $this.editorial.is_like = 0;
          } else {
            $this.editorial.is_like = 1;
          }
          $this.editorial.likes = res.count;
        }
      });
    },
    onCollection() {
      const $this = this;
      let params = {
        thing_id: $this.editorial.thing_id
      };
      GET_THINGCIRCLECOLLECTION(params).then(res => {
        if (res.code == 1) {
          if (res.message == "取消收藏成功") {
            $this.editorial.is_collect = 0;
          } else {
            $this.editorial.is_collect = 1;
          }
          $this.editorial.collects = res.count;
        }
      });
    },
    onAnswer() {
      const $this = this;
      $this.placeholder = $this.editorial.hash
        ? "回复" + $this.editorial.people + ":"
        : "说点什么...";
    },
    onPopMessage() {
      const $this = this;
      $this.is_show = true;
      setTimeout(() => {
        $this.$refs.inputMessage.focus();
      }, 100);
    },
    onChat() {
      let scrollTop =
        window.pageYOffset ||
        document.documentElement.scrollTop ||
        document.body.scrollTop;
      let scrollHeight = document.documentElement.scrollHeight;
      document.documentElement.scrollTop = scrollHeight / 2 - 160;
    }
  }
};
</script>

<style scoped>
.wrap {
  height: 42px;
}
.bom-fixed {
  position: fixed;
  width: 100%;
  height: 40px;
  left: 0;
  bottom: 0;
  background-color: #ffffff;
  z-index: 999;
}
.bom-fixed::before {
  content: "";
  position: absolute;
  pointer-events: none;
  -webkit-box-sizing: border-box;
  box-sizing: border-box;
  top: 0;
  left: 0;
  width: 100%;
  transform: scaleY(0.5);
  height: 1px;
  background-color: #ebedf0;
}
.prt {
  position: relative;
  display: flex;
  align-items: center;
  height: 100%;
  margin: 0px 10px;
}

.prt >>> .van-field {
  background-color: rgba(242, 242, 242, 1);
  padding: 0px;
  font-size: 12px;
  border-radius: 20px;
  padding-left: 10px;
}
.text-input {
  flex: 1;
  color: #999;
  padding: 6px 0px;
  display: flex;
  background-color: #f2f1f1;
  border-radius: 20px;
  margin-right: 10px;
}
.text-input >>> .van-icon-edit {
  margin-left: 10px;
}
.text-input span {
  display: inline-block;
  margin-left: 4px;
}
.icon-w {
  max-width: 80px;
  min-width: 20px;
  padding-right: 10px;
  display: flex;
  font-size: 12px;
  align-items: flex-end;
  color: #999;
}
.icon-w span {
  text-overflow: ellipsis;
  white-space: nowrap;
  display: inline-block;
  overflow: hidden;
}
.cr-ff454e {
  color: #ff454e;
}
.cr-999 {
  color: #999;
}
.msg-input {
  display: flex;
  width: 100%;
  align-items: center;
}
.msg-input >>> .van-field__control {
  border: 1px solid #dadada;
  border-radius: 20px;
  padding-left: 10px;
}
.btn-s {
  width: 40px;
  height: 100%;
  color: #1989fa;
}
</style>