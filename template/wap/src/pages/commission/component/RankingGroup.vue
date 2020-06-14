<template>
  <div class="list">
    <van-tabs type="card" class="tab-card-group" @change="onTab">
      <van-tab :title="item.name" v-for="(item,index) in bTabs" :key="index" />
    </van-tabs>
    <Layout ref="load">
      <div class="rank-box card-group-box" v-if="rankBox.length>0">
        <div class="item" :class="'rank-'+item.ranking" v-for="(item,r) in rankBox" :key="r">
          <div class="img">
            <img :src="item.user_headimg | BASESRC" :onerror="$ERRORPIC.noAvatar" />
            <span class="n">{{item.ranking}}</span>
          </div>
          <div class="name text-nowrap">{{item.nick_name}}</div>
          <div class="total text-nowrap">{{item.number}}{{item.unit}}</div>
        </div>
      </div>
      <van-cell-group class="card-group-box" v-if="listItems.length>0">
        <van-cell center :border="false" class="list-item" v-for="(item,l) in listItems" :key="l">
          <div slot="icon" class="num">{{item.ranking}}</div>
          <div slot="title" class="title">
            <div class="img">
              <img :src="item.user_headimg | BASESRC" :onerror="$ERRORPIC.noAvatar" />
            </div>
            <span class="name">{{item.nick_name}}</span>
          </div>
          <div slot="right-icon" class="total">
            <span class="text-maintone">{{item.number}}</span>{{item.unit}}
          </div>
        </van-cell>
      </van-cell-group>
    </Layout>
  </div>
</template>

<script>
import { Tab, Tabs } from "vant";
export default {
  data() {
    return {
      bTabs: [
        {
          name: "月榜",
          times: "month"
        },
        {
          name: "年榜",
          times: "year"
        },
        {
          name: "总榜",
          times: "all"
        }
      ]
    };
  },
  props: {
    list: Array
  },
  watch: {
    "list.length"(e) {
      this.load();
    }
  },
  computed: {
    rankBox() {
      const list = this.list.filter(e => {
        if (e.ranking == 1) {
          e.sort = 2;
        }
        if (e.ranking == 2) {
          e.sort = 1;
        }
        if (e.ranking == 3) {
          e.sort = 3;
        }
        if (e.ranking < 4) {
          return e;
        }
      });
      return list.sort((a, b) => a.sort - b.sort);
    },
    listItems() {
      const list = this.list.filter(e => {
        if (e.ranking > 3) {
          return e;
        }
      });
      return list;
    }
  },
  mounted() {
    this.load();
  },
  methods: {
    onTab(e) {
      this.$emit("change", this.bTabs[e].times);
    },
    load() {
      if (this.list.length > 0) {
        this.$refs.load.success();
      } else {
        this.$refs.load.fail({
          errorType: "fail",
          errorText: "暂无排行",
          showFoot: false
        });
      }
    }
  },
  components: {
    [Tab.name]: Tab,
    [Tabs.name]: Tabs
  }
};
</script>

<style scoped>
.list {
  margin-bottom: 80px;
}

.tab-card-group {
  margin: 10px 0;
}

.rank-box {
  display: flex;
  justify-content: space-around;
  background: #ff454e;
  padding: 15px 10px;
}

.rank-box .item {
  display: flex;
  flex: 1;
  flex-flow: column;
  color: #fff;
  font-size: 12px;
  text-align: center;
  line-height: 1.6;
  justify-content: flex-end;
}

.rank-box .item .img {
  position: relative;
  width: 60px;
  height: 60px;
  margin: 0 auto;
  margin-bottom: 15px;
}

.rank-box .item .img .n {
  position: absolute;
  bottom: -8px;
  left: 50%;
  margin-left: -8px;
  display: block;
  width: 18px;
  height: 18px;
  line-height: 18px;
  background: #fff;
  color: #666;
  border-radius: 50%;
  font-size: 10px;
}

.rank-box .item .img img {
  display: block;
  width: 100%;
  height: 100%;
  border-radius: 50%;
}

.rank-box .item .name,
.rank-box .item .total {
  max-width: 80px;
  margin: 0 auto;
  height: 20px;
}

.rank-box .item.rank-1 .img {
  width: 80px;
  height: 80px;
}

.rank-box .item.rank-1 .img .n {
  background: #ffca07;
  color: #f35e06;
}

.rank-box .item.rank-2 .img .n {
  background: #f3d66e;
  color: #f35e06;
}

.rank-box .item.rank-3 .img .n {
  background: #e6e1ce;
  color: #c76d37;
}

.list-item .num {
  font-size: 16px;
  margin-right: 20px;
  color: #666;
  display: block;
  width: 20px;
  text-align: center;
}

.list-item .title {
  display: flex;
  margin-right: 10px;
  align-items: center;
}

.list-item .img {
  width: 40px;
  height: 40px;
  margin-right: 10px;
}

.list-item .img img {
  display: block;
  width: 100%;
  height: 100%;
  border-radius: 50%;
}
</style>