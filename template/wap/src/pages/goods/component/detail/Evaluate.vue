<template>
  <div class="evaluate">
    <van-tabs :swipe-threshold="5" @change="onTab">
      <van-tab v-for="(item,index) in tabs" :title="item.name+'('+item.count+')'" :key="index" />
    </van-tabs>
    <van-cell-group class="items" v-if="list.length>0">
      <van-cell v-for="(item,index) in list" :key="index">
        <van-row type="flex" justify="space-between" class="head">
          <van-col span="14" class="info">
            <div class="img">
              <img :src="item.user_img" :onerror="$ERRORPIC.noAvatar" />
            </div>
            <div class="user">
              <div
                class="name"
              >{{item.user_name?item.user_name:(item.nick_name?item.nick_name:'匿名')}}</div>
              <span class="score">{{item.explain_type | explainText}}</span>
            </div>
          </van-col>
          <van-col span="10" class="time">{{item.addtime | formatDate}}</van-col>
        </van-row>
        <div class="content-item">
          <div class="content">{{item.content}}</div>
          <div class="imgs" v-if="item.images[0]">
            <ImagePanelPreview :show-delete="false" :list="item.images" />
          </div>
        </div>
        <div class="content-item" v-if="item.explain_first">
          <div class="title">[商家回复]：</div>
          <div class="content">{{item.explain_first}}</div>
        </div>
        <div class="content-item" v-if="item.again_content || item.again_images[0]">
          <div class="title">追评：</div>
          <div class="content">{{item.again_content}}</div>
          <div class="imgs" v-if="item.again_images[0]">
            <ImagePanelPreview :show-delete="false" :list="item.again_images" />
          </div>
        </div>
        <div class="content-item" v-if="item.again_explain">
          <div class="title">[追评回复]：</div>
          <div class="content">{{item.again_explain}}</div>
        </div>
      </van-cell>
    </van-cell-group>
    <div class="empty" v-else>暂无评价</div>
  </div>
</template>

<script>
import { GET_GOODSEVALUATE } from "@/api/goods";
import { isEmpty } from "@/utils/util";
import ImagePanelPreview from "@/components/ImagePanelPreview";
export default {
  data() {
    const goods_id = this.data || null;
    return {
      evaluate_count: 0,
      imgs_count: 0,
      praise_count: 0,
      center_count: 0,
      bad_count: 0,

      list: [],

      params: {
        goods_id,
        page_index: 1,
        page_size: 20,
        is_image: null,
        explain_type: null
      }
    };
  },
  props: {
    data: [Number, String]
  },
  filters: {
    explainText(value) {
      let text = "";
      if (value == 5) {
        text = "好评";
      } else if (value == 3) {
        text = "中评";
      } else if (value == 1) {
        text = "差评";
      }
      return text;
    }
  },
  watch: {
    params: {
      deep: true,
      handler(n, o) {
        this.loadData();
      }
    }
  },
  computed: {
    tabs() {
      return [
        {
          name: "全部",
          type: null,
          count: this.evaluate_count
        },
        {
          name: "图片",
          type: true,
          count: this.imgs_count
        },
        {
          name: "好评",
          type: 5,
          count: this.praise_count
        },
        {
          name: "中评",
          type: 3,
          count: this.center_count
        },
        {
          name: "差评",
          type: 1,
          count: this.bad_count
        }
      ];
    }
  },
  mounted() {
    const $this = this;
    $this.loadData();
  },
  methods: {
    loadData() {
      const $this = this;
      GET_GOODSEVALUATE($this.params).then(({ data }) => {
        $this.list = data.review_list;
        $this.evaluate_count = data.evaluate_count;
        $this.imgs_count = data.imgs_count;
        $this.praise_count = data.praise_count;
        $this.center_count = data.center_count;
        $this.bad_count = data.bad_count;
      });
    },
    onTab(index) {
      const $this = this;
      const type = $this.tabs[index].type;
      if (type === true) {
        $this.params.is_image = true;
        $this.params.explain_type = null;
      } else {
        $this.params.is_image = null;
        $this.params.explain_type = type;
      }
    }
  },
  components: {
    ImagePanelPreview
  }
};
</script>

<style scoped>
.items .head .info {
  display: flex;
}

.items .head .info .img {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  overflow: hidden;
}

.items .head .info .img img {
  width: 100%;
  height: 100%;
}

.items .head .info .user {
  padding-left: 10px;
  line-height: 20px;
}

.items .head .info .user .score {
  font-size: 12px;
  color: #666;
}

.items .head .time {
  white-space: nowrap;
  text-align: right;
  color: #666;
  font-size: 12px;
}

.items .content-item {
  margin: 10px 0;
}

.items .content-item .title {
  font-size: 12px;
  color: #af7119;
}
</style>
