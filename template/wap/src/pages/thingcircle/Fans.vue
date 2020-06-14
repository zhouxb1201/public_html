<template>
  <div class="thingcircle-fans">
    <Navbar :isMenu="false" />
    <List
      class="list"
      v-model="loading"
      :finished="finished"
      :finishedText="null"
      :error.sync="error"
      :is-empty="isListEmpty"
      :empty="{message: '木有粉丝~', showFoot: false}"
      @load="loadList"
    >
      <div class="cell" v-for="(item,index) in list" :key="index">
        <img :src="item.user_headimg" :onerror="$ERRORPIC.noAvatar" class="img" />
        <div class="info">
          <span>{{item.thing_user_name ? item.thing_user_name : "匿名"}}</span>
          <span>干货·{{item.thing_count ? item.thing_count : 0}}</span>
        </div>
        <div class="btn" v-if="item.mutual">
          <van-button
            plain
            round
            class="gray"
            type="danger"
            size="small"
            @click="sensitiveOthers(item.uid,index)"
          >互相关注</van-button>
        </div>
        <div class="btn" v-else>
          <van-button round type="danger" size="small" @click="sensitiveOthers(item.uid,index)">回粉</van-button>
        </div>
      </div>
    </List>
  </div>
</template>

<script>
import sfc from "@/utils/create";
import {
  GET_FANSUSERLIST,
  GET_THINGCIRCLEFOLLOW,
  GET_SHAREINFO
} from "@/api/thingcircle";
import { list } from "@/mixins";
import { filterUriParams } from "@/utils/util";
export default sfc({
  name: "thingcircle-fans",
  data() {
    return {
      params: {
        page_index: 1,
        page_size: 10
      }
    };
  },
  mixins: [list],
  mounted() {
    this.loadList();
  },
  methods: {
    loadList(init) {
      const $this = this;
      GET_FANSUSERLIST($this.params).then(({ data }) => {
        let list = data.data;
        $this.pushToList(list, data.page_count, init);
      });
      $this.getShareInfo();
    },
    sensitiveOthers(id, index) {
      let param = {
        thing_auid: id
      };
      GET_THINGCIRCLEFOLLOW(param).then(res => {
        if (res.code == 1) {
          if (res.message == "关注成功") {
            this.list[index].mutual = 1;
          } else {
            this.list[index].mutual = 0;
          }
        }
      });
    },
    getShareInfo() {
      const $this = this;
      GET_SHAREINFO().then(({ data }) => {
        $this.onShare({
          title: data.other_title ? data.other_title : "好物圈",
          desc: data.other_describe
            ? data.other_describe
            : `我刚刚在${$this.$store.getters.config.mall_name}发现了一个很不错的好物圈，赶快来看看吧。`,
          imgUrl: data.other_pic,
          link:
            $this.$store.state.domain +
            "/wap" +
            $this.$route.path +
            filterUriParams($this.$route.query, "extend_code")
        });
      });
    }
  }
});
</script>

<style scoped>
.cell {
  display: flex;
  padding: 10px;
  position: relative;
  background-color: #ffffff;
}
.cell::after {
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
.cell .img {
  width: 50px;
  height: 50px;
  display: block;
  border-radius: 50%;
}
.cell .info {
  flex: 1;
  line-height: 24px;
  padding-left: 10px;
}
.cell .info span {
  display: block;
}
.cell .info span:last-child {
  font-size: 12px;
  color: #999;
}
.cell .btn {
  width: 70px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.cell .btn .gray {
  color: #999;
  border: 1px solid #e5e5e5;
}
</style>