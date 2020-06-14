<template>
  <div class="b-wrap">
    <div class="b-fixed">
      <div class="b-prt">
        <div class="b-text-input" @click="$emit('openSend')">
          <van-icon name="edit" size="14px" />
          <span>说点什么...</span>
        </div>
        <div class="icon-w" @click="onFabulous()">
          <van-icon
            :name="items.is_like ? 'like' : 'like-o'"
            :class="items.is_like ? color.yes : color.no"
            size="20px"
          />
          <span>{{items.likes | praise}}</span>
        </div>
        <div class="icon-w" @click="onCollection()">
          <van-icon
            :name="items.is_collect ? 'star' : 'star-o'"
            :class="items.is_collect ? color.yes : color.no"
            size="20px"
          />
          <span>{{items.collects | praise}}</span>
        </div>
        <div class="icon-w" @click="$emit('openComment')">
          <van-icon name="chat-o" :class="color.no" size="20px" />
          <span></span>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import {
  GET_THINGCIRCLELIKES,
  GET_THINGCIRCLECOLLECTION
} from "@/api/thingcircle";
export default {
  data() {
    return {
      color: {
        yes: "cr-ff454e",
        no: "cr-fff"
      }
    };
  },
  props: {
    items: [Object]
  },
  methods: {
    //点赞
    onFabulous() {
      const $this = this;
      let params = {
        thing_id: $this.items.id
      };
      GET_THINGCIRCLELIKES(params).then(res => {
        if (res.code == 1) {
          if (res.message == "取消点赞成功") {
            $this.items.is_like = 0;
          } else {
            $this.items.is_like = 1;
          }
          $this.items.likes = res.count;
        }
      });
    },
    //收藏
    onCollection() {
      const $this = this;
      let params = {
        thing_id: $this.items.id
      };
      GET_THINGCIRCLECOLLECTION(params).then(res => {
        if (res.code == 1) {
          if (res.message == "取消收藏成功") {
            $this.items.is_collect = 0;
          } else {
            $this.items.is_collect = 1;
          }
          $this.items.collects = res.count;
        }
      });
    }
  }
};
</script>

<style scoped>
.b-wrap {
  height: 42px;
  background-color: #000;
}
.b-fixed {
  position: fixed;
  width: 100%;
  height: 40px;
  left: 0;
  bottom: 0;
  z-index: 999;
}
.b-prt {
  display: flex;
  align-items: center;
  height: 100%;
  margin: 0px 10px;
}
.b-fixed::before {
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
.b-text-input {
  flex: 1;
  padding-right: 10px;
  color: #fff;
  padding: 8px 0px;
  display: flex;
}
.b-text-input span {
  display: inline-block;
  margin-left: 4px;
}
.icon-w {
  max-width: 80px;
  min-width: 20px;
  padding-right: 10px;
  display: flex;
  font-size: 14px;
  align-items: center;
  color: #fff;
}
.icon-w span {
  text-overflow: ellipsis;
  white-space: nowrap;
  display: inline-block;
  overflow: hidden;
  margin-left: 6px;
}
.cr-ff454e {
  color: #ff454e;
}
.cr-fff {
  color: #fff;
}
</style>