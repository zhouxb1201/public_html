<template>
  <div class="box">
    <div class="item" v-for="(item,index) in items" :key="index" @click="toPath(index)">
      <div class="top">
        <img class="img" :src="item.imgUrl" :onerror="$ERRORPIC.noGoods" />
        <span class="dot" v-if="item.badge"></span>
      </div>
      <div class="text">{{item.text}}</div>
    </div>
  </div>
</template>

<script>
import { GET_THINGCIRCLEMESSAGECENTER } from "@/api/thingcircle";
export default {
  data() {
    return {
      items: [
        {
          imgUrl: this.$BASEIMGPATH + "pic_message.png",
          text: "消息通知",
          badge: 0
        },
        {
          imgUrl: this.$BASEIMGPATH + "pic_give_up.png",
          text: "赞和收藏",
          badge: 0
        },
        {
          imgUrl: this.$BASEIMGPATH + "pic_at.png",
          text: "评论和@",
          badge: 0
        }
      ]
    };
  },
  mounted() {
    this.loadData();
  },
  methods: {
    loadData() {
      const $this = this;
      GET_THINGCIRCLEMESSAGECENTER().then(({ data }) => {
        $this.items[0].badge = data.message_count;
        $this.items[1].badge = data.lac_count;
        $this.items[2].badge = data.comment_count;
      });
    },
    toPath(index) {
      if (index == 0) {
        this.$router.push("/message/notice");
      } else {
        let hash;
        if (index == 1) {
          hash = "#collect";
        } else if (index == 2) {
          hash = "#at";
        }
        this.$router.push({
          name: "message-list",
          hash: hash
        });
      }
      this.items[index].badge = 0;
    }
  }
};
</script>

<style scoped>
.box {
  display: flex;
  background-color: #fff;
  padding: 12px 0px;
  margin-bottom: 10px;
}
.box .item {
  flex: 1;
  text-align: center;
  position: relative;
}

.box .item .top {
  position: relative;
  width: 48px;
  height: 48px;
  margin: auto;
}
.box .item .top .dot {
  display: block;
  position: absolute;
  right: -2px;
  top: -2px;
  width: 8px;
  height: 8px;
  z-index: 10;
  background: red;
  border-radius: 100%;
  color: #fff;
  font-size: 10px;
}
.box .item .img {
  width: 48px;
  height: 48px;
  display: block;
  border-radius: 8px;
}

.box .item .text {
  padding-top: 8px;
}
</style>